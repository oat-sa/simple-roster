<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class FlysystemFileStorage implements FileStorageInterface
{
    public function __construct(
        private readonly FilesystemOperator $filesystem
    ) {
    }

    public function store(UploadedFile $file, string $key, array $metadata = []): void
    {
        $stream = @fopen($file->getPathname(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Unable to open uploaded file stream.');
        }

        try {
            $config = ['Metadata' => $metadata];
            $mimeType = $file->getClientMimeType();
            if (is_string($mimeType) && $mimeType !== '') {
                $config['ContentType'] = $mimeType;
            }

            $this->filesystem->writeStream($key, $stream, $config);
        } catch (FilesystemException|Throwable $exception) {
            throw new RuntimeException(
                sprintf('Unable to upload file to storage key "%s".', $key),
                0,
                $exception
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
