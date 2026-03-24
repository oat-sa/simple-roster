<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\RosteringImport;

use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusService;
use OAT\SimpleRoster\Tests\AppWebTestCase;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GetRosteringImportDownloadActionTest extends AppWebTestCase
{
    use DatabaseTestingTrait;

    private const DOWNLOAD_ENDPOINT_PREFIX = '/api/v1/download/';
    private const AUTH_TEST_REFERENCE_ID = '76091d1a-3ef5-438d-a88f-8df73bb5f919';
    private const NOT_FOUND_REFERENCE_ID = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const INVALID_REFERENCE_ID = 'ref..invalid';

    private KernelBrowser $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer testApiKey']);
        $this->setUpDatabase();
    }

    public function testItRequiresApiKeyAuthentication(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_GET,
            self::DOWNLOAD_ENDPOINT_PREFIX . self::AUTH_TEST_REFERENCE_ID,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid']
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturnsNotFoundWhenDownloadIsNotAvailable(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, self::DOWNLOAD_ENDPOINT_PREFIX . self::NOT_FOUND_REFERENCE_ID);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturnsBadRequestForInvalidReferenceId(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, self::DOWNLOAD_ENDPOINT_PREFIX . self::INVALID_REFERENCE_ID);

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturnsBadRequestWhenDownloadResolutionFails(): void
    {
        $statusService = $this->createMock(RosteringImportStatusService::class);
        $statusService
            ->expects(self::once())
            ->method('getDownloadUrl')
            ->with(self::AUTH_TEST_REFERENCE_ID)
            ->willThrowException(new RosteringStatusException('Unable to merge worker output files.'));
        self::getContainer()->set(RosteringImportStatusService::class, $statusService);

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            self::DOWNLOAD_ENDPOINT_PREFIX . self::AUTH_TEST_REFERENCE_ID
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());
        $decodedResponse = json_decode(
            (string) $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('Unable to resolve rostering import download URL.', $decodedResponse['error']['message']);
    }

    public function testItReturnsSignedUrlWhenDownloadIsAvailable(): void
    {
        $statusService = $this->createMock(RosteringImportStatusService::class);
        $statusService
            ->expects(self::once())
            ->method('getDownloadUrl')
            ->with(self::AUTH_TEST_REFERENCE_ID)
            ->willReturn('https://signed-url');
        self::getContainer()->set(RosteringImportStatusService::class, $statusService);

        $this->kernelBrowser->request(
            Request::METHOD_GET,
            self::DOWNLOAD_ENDPOINT_PREFIX . self::AUTH_TEST_REFERENCE_ID
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
        $decodedResponse = json_decode(
            (string) $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('https://signed-url', $decodedResponse['signedUrl']);
    }
}
