<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\RowToModelMapper\AbstractRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\AbstractModel;
use App\Model\Storage\AbstractModelStorage;
use App\Model\Validation\ValidationException;

abstract class AbstractIngester
{
    /**
     * @var AbstractModelStorage
     */
    protected $modelStorage;

    /**
     * @var AbstractRowToModelMapper
     */
    protected $rowToModelMapper;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(AbstractModelStorage $modelStorage, AbstractRowToModelMapper $rowToModelMapper)
    {
        $this->modelStorage = $modelStorage;
        $this->rowToModelMapper = $rowToModelMapper;
    }

    /**
     * @param String[] $row
     * @return AbstractModel
     */
    abstract protected function convertRowToModel(array $row): AbstractModel;

    /**
     * @param AbstractModel $entity
     * @throws ValidationException
     */
    protected function validateEntity(AbstractModel $entity): void
    {
        $entity->validate();
    }

    /**
     * Checks if the record with same primary key already exists
     *
     * @param AbstractModel $entity
     * @return bool
     */
    protected function checkIfExists(AbstractModel $entity): bool
    {
        return $this->modelStorage->read($this->modelStorage->getKey($entity)) !== null;
    }

    /**
     * @param SourceInterface $source
     * @return array
     * @throws InputOptionException
     * @throws \App\Ingesting\Exception\IngestingException
     * @throws FileLineIsInvalidException
     * @throws \Exception
     */
    public function ingest(SourceInterface $source): array
    {
        $alreadyExistingRowsCount = $rowsAdded = 0;

        $lineNumber = 0;
        foreach ($source->iterateThroughLines() as $line) {
            $lineNumber++;
            $entity = $this->convertRowToModel($line);
            try {
                $this->validateEntity($entity);
            } catch (ValidationException $e) {
                throw new FileLineIsInvalidException($lineNumber, $e->getMessage());
            }

            if ($this->checkIfExists($entity)) {
                $alreadyExistingRowsCount++;
                if (!$this->updateMode) {
                    continue;
                }
            } else {
                if (!$this->updateMode) {
                    $rowsAdded++;
                }
            }

            $this->modelStorage->insert($this->modelStorage->getKey($entity), $entity);
        }

        return [
            'rowsAdded' => $rowsAdded,
            'alreadyExistingRowsCount' => $alreadyExistingRowsCount,
        ];
    }

    public function isUpdateMode(): bool
    {
        return $this->updateMode;
    }
}