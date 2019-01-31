<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Ingesting\Exception\IngestingException;
use App\Model\ModelInterface;
use App\Model\LineItem;

class LineItemRowToModelMapper extends AbstractRowToModelMapper
{
    /**
     * @var StringToDatetimeConverter
     */
    private $stringToDatetimeConverter;

    public function __construct(StringToDatetimeConverter $stringToDatetimeConverter)
    {
        $this->stringToDatetimeConverter = $stringToDatetimeConverter;
    }

    /**
     * @param array $row
     * @param array $fieldNames
     * @return ModelInterface
     * @throws IngestingException
     */
    public function map(array $row, array $fieldNames): ModelInterface
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);

        $startDt = $endDt = null;

        if (!empty($fieldValues['start_date_time'])) {
            $startDt = $this->stringToDatetimeConverter->convert($fieldValues['start_date_time']);
            if ($startDt === null) {
                throw new IngestingException('"start_date_time" of a line item should be of format "2019-01-26 18:30:00" or empty');
            }
        }

        if (!empty($fieldValues['end_date_time'])) {
            $endDt = $this->stringToDatetimeConverter->convert($fieldValues['end_date_time']);
            if ($startDt === null) {
                throw new IngestingException('"end_date_time" of a line item should be of format "2019-01-26 18:30:00" or empty');
            }
        }

        return new LineItem($fieldValues['tao_uri'], $fieldValues['label'], $fieldValues['infrastructure_id'], $startDt, $endDt);
    }
}