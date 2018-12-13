<?php

namespace App\Storage;

interface Storage
{
    public function read(string $storageName, array $key): array;

    public function insert(string $storageName, array $keys, array $data): void;

    public function update(string $storageName, array $keys, array $data): void;

    public function delete(string $storageName, array $keys): void;
}