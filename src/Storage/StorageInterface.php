<?php

namespace App\Storage;

/**
 * Simple interface for noSQL database. Currently allows only use primary keys entirely,
 * i.e. without searching by partial (hash) key, which can be a HASH part of a composite key
 *
 * Interface Storage
 * @package App\Storage
 */
interface StorageInterface
{
    /**
     * Reads one record by primary key
     * In case it doesn't exist, return null
     *
     * @param string $tableName
     * @param array $key
     * @return array|null
     */
    public function read(string $tableName, array $key): ?array;

    /**
     * Puts a record with primary key
     *
     * @param string $tableName
     * @param array $key
     * @param array $data
     */
    public function insert(string $tableName, array $key, array $data): void;

    /**
     * Deletes one record by primary key
     *
     * @param string $tableName
     * @param array $key primary key
     */
    public function delete(string $tableName, array $key): void;
}