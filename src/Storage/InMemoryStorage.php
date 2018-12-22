<?php

namespace App\Storage;

class InMemoryStorage implements Storage
{
    private $items = [];

    /**
     * Makes an indicator to use as a key in the table for a given entity
     * Maps its primary key to a string
     *
     * @param string $tableName
     * @param array $keys
     * @return string
     */
    private function hash(string $tableName, array $keys): string
    {
        return $tableName . md5(serialize($keys));
    }

    /**
     * @inheritdoc
     */
    public function read(string $tableName, array $keys): ?array
    {
        $hash = $this->hash($tableName, $keys);
        if (!array_key_exists($hash, $this->items)) {
            return null;
        }

        return $this->items[$hash];
    }

    /**
     * @inheritdoc
     */
    public function insert(string $tableName, array $keys, array $data): void
    {
        $hash = $this->hash($tableName, $keys);
        $this->items[$hash] = $data;
    }

    /**
     * @inheritdoc
     */
    public function delete(string $tableName, array $keys): void
    {
        $hash = $this->hash($tableName, $keys);
        if (!array_key_exists($hash, $this->items)) {
            throw new \OutOfBoundsException('No such item saved');
        }

        unset($this->items[$hash]);
    }
}