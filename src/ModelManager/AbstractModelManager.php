<?php declare(strict_types=1);

namespace App\ModelManager;

use App\Model\ModelInterface;
use App\Storage\StorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractModelManager implements ModelManagerInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Get table name used for a storage
     *
     * @return string
     */
    abstract protected function getTable(): string;

    /**
     * @inheritdoc
     */
    abstract public function getKey(ModelInterface $model): string;

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

    public function __construct(StorageInterface $storage, SerializerInterface $serializer)
    {
        $this->storage = $storage;
        $this->serializer = $serializer;
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
    public function read(string $key): ?ModelInterface
    {
        $rowData = $this->readRawData($key);

        if ($rowData !== null) {
            /** @var ModelInterface $model */
            $model = $this->serializer->deserialize($rowData, $this->getModelClass(), 'plain');
            return $model;
        }

        return null;
    }

    /**
     * @param ModelInterface $model
     * @throws \Exception
     */
    protected function assertModelClass(ModelInterface $model): void
    {
        $modelClass = $this->getModelClass();
        if (!$model instanceof $modelClass) {
            throw new \Exception(sprintf('Model should be of type "%s"', $modelClass));
        }
    }

    /**
     * @inheritdoc
     */
    public function insert(ModelInterface $model): void
    {
        $this->assertModelClass($model);
        /** @var array $normalizedModel */
        $normalizedModel = $this->serializer->serialize($model, 'plain');
        $this->storage->insert($this->getTable(), [$this->getKeyFieldName() => $this->getKey($model)], $normalizedModel);
    }

    /**
     * @inheritdoc
     */
    public function delete(string $key): void
    {
        $this->storage->delete($this->getTable(), [$this->getKeyFieldName() => $key]);
    }
}