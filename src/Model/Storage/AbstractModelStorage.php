<?php

namespace App\Model\Storage;

use App\Model\AbstractModel;
use App\Storage\StorageInterface;

abstract class AbstractModelStorage implements ModelStorageInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Get table name used for a storage
     *
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * @inheritdoc
     */
    abstract public function getKey(AbstractModel $model): string;

    /**
     * Returns primary key value of a model
     *
     * @return string
     */
    abstract protected function getKeyFieldName(): string;

    /**
     * @return string php class name
     */
    abstract protected function getModelClass(): string;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Returns data as array
     *
     * @param string $key
     * @return array|null
     */
    protected function readRawData(string $key): ?array
    {
        return $this->storage->read($this->getTable(), [$this->getKeyFieldName() => $key]);
    }

    /**
     * @inheritdoc
     */
    public function read(string $key): ?AbstractModel
    {
        $rowData = $this->readRawData($key);
        if ($rowData) {
            $modelClass = $this->getModelClass();
            return new $modelClass($rowData);
        }

        return null;
    }

    /**
     * @param AbstractModel $model
     * @throws \Exception
     */
    protected function assertModelClass(AbstractModel $model): void
    {
        $modelClass = $this->getModelClass();
        if (!$model instanceof $modelClass) {
            throw new \Exception(sprintf('Model should be of type "%s"', $modelClass));
        }
    }

    /**
     * @inheritdoc
     */
    public function insert(string $key, AbstractModel $model): void
    {
        $this->assertModelClass($model);
        $this->storage->insert($this->getTable(), [$this->getKeyFieldName() => $key], $model->toArray());
    }

    /**
     * @inheritdoc
     */
    public function delete(string $key): void
    {
        $this->storage->delete($this->getTable(), [$this->getKeyFieldName() => $key]);
    }
}