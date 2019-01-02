<?php

namespace App\Model\Storage;

use App\Model\Model;
use App\Storage\Storage;

abstract class ModelStorage
{
    /**
     * @var Storage
     */
    protected $storage;

    abstract protected function getTable(): string;

    abstract public function getKey(Model $model): string;

    abstract protected function getKeyFieldName(): string;

    abstract protected function getModelClass(): string;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    protected function readRawData(string $key): ?array
    {
        return $this->storage->read($this->getTable(), [$this->getKeyFieldName() => $key]);
    }

    public function read(string $key): ?Model
    {
        $rowData = $this->readRawData($key);
        if ($rowData) {
            $modelClass = $this->getModelClass();
            return new $modelClass($rowData);
        }

        return null;
    }

    /**
     * @param Model $model
     * @throws \Exception
     */
    protected function assertModelClass(Model $model): void
    {
        $modelClass = $this->getModelClass();
        if (!$model instanceof $modelClass) {
            throw new \Exception(sprintf('Model should be of type "%s"', $modelClass));
        }
    }

    /**
     * @param string $key
     * @param Model $model
     * @throws \Exception
     */
    public function insert(string $key, Model $model): void
    {
        $this->assertModelClass($model);
        $this->storage->insert($this->getTable(), [$this->getKeyFieldName() => $key], $model->toArray());
    }

    public function delete(string $key): void
    {
        $this->storage->delete($this->getTable(), [$this->getKeyFieldName() => $key]);
    }
}