<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\RowToModelMapper\AbstractRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\ModelInterface;
use App\Model\Storage\AbstractModelStorage;
use App\Model\Validation\AbstractModelValidator;
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
     * @var AbstractModelValidator
     */
    protected $validator;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(AbstractModelStorage $modelStorage, AbstractRowToModelMapper $rowToModelMapper, AbstractModelValidator $validator)
    {
        $this->modelStorage = $modelStorage;
        $this->rowToModelMapper = $rowToModelMapper;
        $this->validator = $validator;
    }

    /**
     * @param String[] $row
     * @return ModelInterface
     */
    abstract protected function convertRowToModel(array $row): ModelInterface;

    /**
     * Checks if the record with same primary key already exists
     *
     * @param ModelInterface $entity
     * @return bool
     */
    protected function checkIfExists(ModelInterface $entity): bool
    {
        return $this->modelStorage->read($this->modelStorage->getKey($entity)) !== null;
    }

    /**
     * @param SourceInterface $source
     * @param bool $dryRun
     * @return array
     * @throws InputOptionException
     * @throws \App\Ingesting\Exception\IngestingException
     * @throws FileLineIsInvalidException
     * @throws \Exception
     */
    public function ingest(SourceInterface $source, bool $dryRun): array
    {
        $alreadyExistingRowsCount = $rowsAdded = 0;

        $lineNumber = 0;
        foreach ($source->iterateThroughLines() as $line) {
            $lineNumber++;
            $model = $this->convertRowToModel($line);
            try {
                $this->validator->validate($model);
            } catch (ValidationException $e) {
                throw new FileLineIsInvalidException($lineNumber, $e->getMessage());
            }

            if ($this->checkIfExists($model)) {
                $alreadyExistingRowsCount++;
                if (!$this->updateMode) {
                    continue;
                }
            } else {
                if (!$this->updateMode) {
                    $rowsAdded++;
                }
            }

            if (!$dryRun) {
                $this->modelStorage->insert($this->modelStorage->getKey($model), $model);
            }
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