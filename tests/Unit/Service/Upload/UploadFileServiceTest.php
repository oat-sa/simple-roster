<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Upload;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\Service\Upload\FileStorageInterface;
use OAT\SimpleRoster\Service\Upload\UploadedFileValidator;
use OAT\SimpleRoster\Service\Upload\UploadFileService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class UploadFileServiceTest extends TestCase
{
    private const CSV_DELIMITER = ',';
    private const CSV_ENCLOSURE = '"';
    private const CSV_ESCAPE = '\\';

    public function testItStoresFileAndDispatchesMessage(): void
    {
        $validator = new UploadedFileValidator(
            1024,
            100,
            self::CSV_DELIMITER,
            self::CSV_ENCLOSURE,
            self::CSV_ESCAPE
        );

        $storage = $this->createMock(FileStorageInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $service = new UploadFileService($validator, $storage, $bus);

        $file = $this->createUploadedFile('test.csv', 'a,b');

        $storage
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->identicalTo($file),
                $this->matchesRegularExpression('#^[0-9a-f-]{36}/input\.csv$#'),
                $this->callback(static function (array $metadata): bool {
                    return isset($metadata['referenceId']) && is_string($metadata['referenceId']) && $metadata['referenceId'] !== '';
                })
            );

        $bus
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (object $message): Envelope {
                    self::assertInstanceOf(RosteringFileUploadedMessage::class, $message);

                    return new Envelope(new RosteringFileUploadedMessage('ref'));
                }
            );

        $result = $service->upload($file);

        $this->assertSame('File uploaded', $result['message']);
        $this->assertMatchesRegularExpression('#^[0-9a-f-]{36}$#', $result['referenceId']);
    }

    private function createUploadedFile(string $originalName, string $content): UploadedFile
    {
        $tmpDir = sys_get_temp_dir();
        $path = $tmpDir . '/' . uniqid('upload_', true) . '_' . $originalName;
        file_put_contents($path, $content);

        return new UploadedFile($path, $originalName, null, null, true);
    }
}
