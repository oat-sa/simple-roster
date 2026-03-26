<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

interface FileStorageInterface
{
    public function exists(string $key): bool;

    /**
     * @return resource
     */
    public function read(string $key);

    /**
     * @param resource $stream
     * @param array<string, mixed> $config
     */
    public function store($stream, string $key, array $config = []): string;
}
