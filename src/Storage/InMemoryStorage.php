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
     * @param array $key
     * @return string
     */
    private function hash(string $tableName, array $key): string
    {
        return $tableName . md5(serialize($key));
    }

    /**
     * @inheritdoc
     */
    public function read(string $tableName, array $key): ?array
    {
        $hash = $this->hash($tableName, $key);
        if (!array_key_exists($hash, $this->items)) {
            return null;
        }

        return $this->items[$hash];
    }

    /**
     * @inheritdoc
     */
    public function insert(string $tableName, array $key, array $data): void
    {
        $hash = $this->hash($tableName, $key);
        $this->items[$hash] = $data;
    }

    /**
     * @inheritdoc
     */
    public function delete(string $tableName, array $key): void
    {
        $hash = $this->hash($tableName, $key);
        if (!array_key_exists($hash, $this->items)) {
            throw new \OutOfBoundsException('No such item saved');
        }

        unset($this->items[$hash]);
    }
}