<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\RowToModelMapper\AbstractRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\ModelInterface;
use App\ModelManager\AbstractModelManager;
use App\Validation\ModelValidator;
use App\Validation\ValidationException;

abstract class AbstractIngester
{
    /**
     * @var AbstractModelManager
     */
    protected $modelStorage;

    /**
     * @var AbstractRowToModelMapper
     */
    protected $rowToModelMapper;

    /**
     * @var ModelValidator
     */
    protected $validator;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(AbstractModelManager $modelStorage, AbstractRowToModelMapper $rowToModelMapper, ModelValidator $validator)
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
            try {
                $model = $this->convertRowToModel($line);
                $this->validator->validate($model);
            } catch (ValidationException $e) {
                throw new FileLineIsInvalidException($lineNumber, $e->getMessage());
            } catch (\Throwable $e) {
                throw new FileLineIsInvalidException($lineNumber, 'Can not construct model. Please fill out all the fields.');
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
                $this->modelStorage->insert($model);
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