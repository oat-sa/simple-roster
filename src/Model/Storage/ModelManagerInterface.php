<?php declare(strict_types=1);

namespace App\Model\Storage;

use App\Model\ModelInterface;

interface ModelManagerInterface
{
    /**
     * Returns primary key value of a model
     *
     * @param ModelInterface $model
     * @return string
     */
    public function getKey(ModelInterface $model): string;

    public function read(string $key): ?ModelInterface;

    /**
     * @param string $key
     * @param ModelInterface $model
     * @throws \Exception
     */
    public function insert(string $key, ModelInterface $model): void;

    public function delete(string $key): void;
}