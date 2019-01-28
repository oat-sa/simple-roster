<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\IngestingException;
use App\Ingesting\RowToModelMapper\AbstractRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\ModelInterface;
use App\ODM\ItemManagerInterface;

abstract class AbstractIngester implements IngesterInterface
{
    protected $rowToModelMapper;
    protected $validator;
    private $itemManager;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;


    public function __construct(ItemManagerInterface $itemManager, AbstractRowToModelMapper $rowToModelMapper)
    {
        $this->rowToModelMapper = $rowToModelMapper;
        $this->itemManager = $itemManager;
    }

    /**
     * @param String[] $row
     * @return ModelInterface
     */
    abstract protected function convertRowToModel(array $row): ModelInterface;

    /**
     * @param SourceInterface $source
     * @param bool            $wetRun
     * @return array
     * @throws FileLineIsInvalidException
     * @throws IngestingException
     */
    public function ingest(SourceInterface $source, bool $wetRun): array
    {
        $alreadyExistingRowsCount = $rowsAdded = $lineNumber = 0;

        foreach ($source->iterateThroughLines() as $line) {
            $lineNumber++;
            try {
                $model = $this->convertRowToModel($line);

                if ($this->itemManager->isExist($model)) {
                    $alreadyExistingRowsCount++;
                    if (!$this->updateMode) {
                        continue;
                    }
                } else {
                    $rowsAdded++;
                }

                if ($wetRun) {
                    $this->itemManager->save($model);
                }
            } catch (\Throwable $e) {
                throw new FileLineIsInvalidException($lineNumber, $e->getMessage());
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

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_USER_AND_ASSIGNMENT,
            self::TYPE_LINE_ITEM,
            self::TYPE_INFRASTRUCTURE,
        ];
    }
}