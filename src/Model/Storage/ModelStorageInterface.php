<?php declare(strict_types=1);

namespace App\Model\Storage;

use App\Model\AbstractModel;

interface ModelStorageInterface
{
    /**
     * Returns primary key value of a model
     *
     * @param AbstractModel $model
     * @return string
     */
    public function getKey(AbstractModel $model): string;

    public function read(string $key): ?AbstractModel;

    /**
     * @param string $key
     * @param AbstractModel $model
     * @throws \Exception
     */
    public function insert(string $key, AbstractModel $model): void;

    public function delete(string $key): void;
}