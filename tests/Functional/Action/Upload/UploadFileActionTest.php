<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\Upload;

use OAT\SimpleRoster\Service\Upload\FileStorageInterface;
use OAT\SimpleRoster\Tests\AppWebTestCase;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadFileActionTest extends AppWebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    private KernelBrowser $kernelBrowser;


    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . 'testApiKey']);

        $this->setUpDatabase();
        $this->setUpTestLogHandler();
    }

    public function testItThrowsUnauthorizedHttpExceptionIfRequestApiKeyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/upload',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid'],
            '{}'
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('API key authentication failure.', $decodedResponse['error']['message']);
    }

    public function testItReturns400WhenNoFileProvided(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/upload'
        );

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->kernelBrowser->getResponse()->getStatusCode(),
            (string) $this->kernelBrowser->getResponse()->getContent()
        );

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('No file found in the request.', $decodedResponse['error']['message']);
    }

    public function testItUploadsFileAndReturnsReferenceId(): void
    {
        /** @var FileStorageInterface&MockObject $storage */
        $storage = $this->createMock(FileStorageInterface::class);

        $captured = ['key' => null, 'metadata' => null];

        $storage
            ->method('store')
            ->willReturnCallback(static function (UploadedFile $file, string $storageKey, array $metadata) use (&$captured): void {
                $captured['key'] = $storageKey;
                $captured['metadata'] = $metadata;
            });

        self::getContainer()->set('test.file_storage', $storage);
        self::assertSame($storage, self::getContainer()->get(FileStorageInterface::class));

        $file = $this->createUploadedFile('test.csv', 'a,b');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/upload',
            [],
            ['file' => $file]
        );

        self::assertSame(
            Response::HTTP_OK,
            $this->kernelBrowser->getResponse()->getStatusCode(),
            (string) $this->kernelBrowser->getResponse()->getContent()
        );

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('result', $decodedResponse);
        self::assertSame('File uploaded', $decodedResponse['result']['message']);
        self::assertMatchesRegularExpression('#^[0-9a-f-]{36}$#', $decodedResponse['result']['referenceId']);

        self::assertSame(
            'pending/' . $decodedResponse['result']['referenceId'] . '.csv',
            $captured['key']
        );
        self::assertSame($decodedResponse['result']['referenceId'], $captured['metadata']['referenceId'] ?? null);
    }

    public function testItReturns400WhenUploadedFileExtensionIsInvalid(): void
    {
        $file = $this->createUploadedFile('test.xlsx', 'a,b');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/upload',
            [],
            ['file' => $file]
        );

        self::assertSame(
            Response::HTTP_BAD_REQUEST,
            $this->kernelBrowser->getResponse()->getStatusCode(),
            (string) $this->kernelBrowser->getResponse()->getContent()
        );

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            'File extension "xlsx" is not allowed. Allowed extensions are: csv',
            $decodedResponse['error']['message']
        );
    }

    private function createUploadedFile(string $originalName, string $content): UploadedFile
    {
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . '/' . uniqid('upload_', true) . '_' . $originalName;
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, 'text/csv', null, true);
    }
}
