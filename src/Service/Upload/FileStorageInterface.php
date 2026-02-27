<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface FileStorageInterface
{
    /**
     * @param array<string, string> $metadata
     */
    public function store(UploadedFile $file, string $key, array $metadata = []): string;

    /**
     * @param array<string, string> $metadata
     */
    public function move(string $sourceKey, string $targetKey, array $metadata = []): string;
}
