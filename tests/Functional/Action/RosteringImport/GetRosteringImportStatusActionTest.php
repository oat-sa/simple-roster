<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\RosteringImport;

use DateTimeImmutable;
use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusService;
use OAT\SimpleRoster\Tests\AppWebTestCase;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetRosteringImportStatusActionTest extends AppWebTestCase
{
    use DatabaseTestingTrait;

    private const STATUS_ENDPOINT_PREFIX = '/api/v1/status/';
    private const AUTH_TEST_REFERENCE_ID = '76091d1a-3ef5-438d-a88f-8df73bb5f919';
    private const NOT_FOUND_REFERENCE_ID = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const INVALID_REFERENCE_ID = 'ref..invalid';

    private KernelBrowser $kernelBrowser;
    private FileStorageInterface $fileStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer testApiKey']);
        $this->setUpDatabase();
        $this->fileStorage = self::getContainer()->get(FileStorageInterface::class);
    }

    public function testItRequiresApiKeyAuthentication(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_GET,
            self::STATUS_ENDPOINT_PREFIX . self::AUTH_TEST_REFERENCE_ID,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid']
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturnsNotFoundWhenReferenceDoesNotExist(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, self::STATUS_ENDPOINT_PREFIX . self::NOT_FOUND_REFERENCE_ID);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturnsBadRequestForInvalidReferenceId(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, self::STATUS_ENDPOINT_PREFIX . self::INVALID_REFERENCE_ID);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturnsBadRequestWhenStatusResolutionFails(): void
    {
        $statusService = $this->createMock(RosteringImportStatusService::class);
        $statusService
            ->expects(self::once())
            ->method('getStatus')
            ->with(self::AUTH_TEST_REFERENCE_ID)
            ->willThrowException(new RosteringStatusException('Unable to merge worker output files.'));
        self::getContainer()->set(RosteringImportStatusService::class, $statusService);

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            self::STATUS_ENDPOINT_PREFIX . self::AUTH_TEST_REFERENCE_ID
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
        $decodedResponse = json_decode(
            (string) $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('Unable to resolve rostering import status.', $decodedResponse['error']['message']);
    }

    public function testItReturnsPendingWhenInputFileExistsAndImportRowIsMissing(): void
    {
        $referenceId = '11111111-1111-4111-8111-111111111111';
        $this->storeInputFile($referenceId);

        $this->kernelBrowser->request(Request::METHOD_GET, self::STATUS_ENDPOINT_PREFIX . $referenceId);

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            (string) $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame($referenceId, $decodedResponse['result']['referenceId']);
        self::assertSame('pending', $decodedResponse['result']['status']);
        self::assertSame(0, $decodedResponse['result']['fileLine']);
        self::assertSame([], $decodedResponse['result']['messages']);
        self::assertNull($decodedResponse['result']['resultFileUrl']);
    }

    public function testItReturnsProcessingStatusFromImportRow(): void
    {
        $referenceId = '22222222-2222-4222-8222-222222222222';
        $this->createImportRow($referenceId, RosteringImport::STATUS_PROCESSING, null, null, null, null);

        $this->kernelBrowser->request(Request::METHOD_GET, self::STATUS_ENDPOINT_PREFIX . $referenceId);

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            (string) $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame($referenceId, $decodedResponse['result']['referenceId']);
        self::assertSame('processing', $decodedResponse['result']['status']);
        self::assertSame(0, $decodedResponse['result']['fileLine']);
        self::assertSame([], $decodedResponse['result']['messages']);
        self::assertNull($decodedResponse['result']['resultFileUrl']);
    }

    public function testItReturnsFailedStatusWithErrorMessage(): void
    {
        $referenceId = '33333333-3333-4333-8333-333333333333';
        $this->createImportRow($referenceId, RosteringImport::STATUS_FAILED, 'Global import error', 100, 90, 10);

        $this->kernelBrowser->request(Request::METHOD_GET, self::STATUS_ENDPOINT_PREFIX . $referenceId);
        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            (string) $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('failed', $decodedResponse['result']['status']);
        self::assertSame(100, $decodedResponse['result']['fileLine']);
        self::assertSame(['Global import error'], $decodedResponse['result']['messages']);
        self::assertNull($decodedResponse['result']['resultFileUrl']);
    }

    private function storeInputFile(string $referenceId): void
    {
        $stream = fopen('php://temp', 'rb+');
        self::assertNotFalse($stream);
        fwrite($stream, "col1,col2\nv1,v2\n");
        rewind($stream);

        $this->fileStorage->store($stream, sprintf('%s/input.csv', $referenceId));
    }

    private function createImportRow(
        string $referenceId,
        string $status,
        ?string $errorMessage,
        ?int $totalRows,
        ?int $processedRows,
        ?int $failedRows
    ): void {
        $import = (new RosteringImport())
            ->setReferenceId($referenceId)
            ->setStatus($status)
            ->setAttempts(1)
            ->setErrorMessage($errorMessage)
            ->setTotalRows($totalRows)
            ->setProcessedRows($processedRows)
            ->setFailedRows($failedRows)
            ->setStartedAt(new DateTimeImmutable())
            ->setFinishedAt(new DateTimeImmutable());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($import);
        $entityManager->flush();
    }
}
