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
    public function testItStoresFileAndDispatchesMessage(): void
    {
        $validator = new UploadedFileValidator(1024);

        $storage = $this->createMock(FileStorageInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $service = new UploadFileService($validator, $storage, $bus, 'pending');

        $file = $this->createUploadedFile('test.csv', 'a,b');

        $storage
            ->expects($this->once())
            ->method('store')
            ->with(
                $this->identicalTo($file),
                $this->matchesRegularExpression('#^pending/[0-9a-f-]{36}\.csv$#'),
                $this->callback(static function (array $metadata): bool {
                    return isset($metadata['referenceId']) && is_string($metadata['referenceId']) && $metadata['referenceId'] !== '';
                })
            )
            ->willReturn('File uploaded');

        $bus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(RosteringFileUploadedMessage::class))
            ->willReturn(new Envelope(new RosteringFileUploadedMessage('ref', 'pending/ref.csv')));

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
