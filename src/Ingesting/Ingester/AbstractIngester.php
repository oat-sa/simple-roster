<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\AbstractSource;
use App\Ingesting\Source\SourceFactory;
use App\Model\AbstractModel;
use App\Model\Storage\AbstractModelStorage;
use App\Model\Validation\ValidationException;
use App\S3\S3ClientFactory;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractIngester
{
    /**
     * @var AbstractModelStorage
     */
    protected $modelStorage;

    /**
     * @var S3ClientFactory
     */
    protected $s3ClientFactory;

    /**
     * @var SourceFactory
     */
    protected $sourceFactory;

    /**
     * @var RowToModelMapper
     */
    protected $rowToModelMapper;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(AbstractModelStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, RowToModelMapper $rowToModelMapper)
    {
        $this->modelStorage = $modelStorage;
        $this->s3ClientFactory = $s3ClientFactory;
        $this->sourceFactory = $sourceFactory;
        $this->rowToModelMapper = $rowToModelMapper;
    }

    /**
     * @param String[] $row
     * @return AbstractModel
     */
    abstract protected function convertRowToModel(array $row): AbstractModel;

    /**
     * @param InputInterface $input
     * @return AbstractSource
     * @throws InputOptionException
     */
    private function detectSource(array $options): AbstractSource
    {
        $accessParameters = [];
        foreach ($this->sourceFactory->getSupportedAccessParameters() as $parameterName) {
            $accessParameters[$parameterName] = array_key_exists($parameterName, $options) ? $options[$parameterName] : null;
        }
        $accessParameters['s3_client_factory'] = $this->s3ClientFactory;
        try {
            return $this->sourceFactory->createSource($accessParameters);
        } catch (\Exception $e) {
            throw new InputOptionException($e->getMessage());
        }
    }

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
     * @param array $options
     * @return array
     * @throws InputOptionException
     * @throws \App\Ingesting\Exception\IngestingException
     * @throws FileLineIsInvalidException
     * @throws \Exception
     */
    public function ingest(array $options): array
    {
        $alreadyExistingRowsCount = $rowsAdded = 0;

        $lineNumber = 0;
        foreach ($this->detectSource($options)->iterateThroughLines() as $line) {
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