<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Throwable;

class OperationalFileStorage implements FileStorageInterface
{
    public function __construct(
        private readonly FilesystemOperator $filesystem
    ) {
    }

    public function read(string $key)
    {
        try {
            $stream = $this->filesystem->readStream($key);
        } catch (FilesystemException|Throwable $exception) {
            throw new RuntimeException(sprintf('Unable to read file from storage key "%s".', $key), 0, $exception);
        }

        if (!is_resource($stream)) {
            throw new RuntimeException(sprintf('Unable to read file from storage key "%s".', $key));
        }

        return $stream;
    }

    public function store($stream, string $key, array $config = []): string
    {
        if (!is_resource($stream)) {
            throw new RuntimeException('Unable to open uploaded file stream.');
        }

        try {
            $this->filesystem->writeStream($key, $stream, $config);
        } catch (FilesystemException|Throwable $exception) {
            throw new RuntimeException(sprintf('Unable to upload file to storage key "%s".', $key), 0, $exception);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return sprintf('File uploaded to %s', $key);
    }
}
