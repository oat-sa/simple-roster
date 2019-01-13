<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Model\AbstractModel;
use App\Model\LineItem;

class LineItemRowToModelMapper extends AbstractRowToModelMapper
{
    public function map(array $row, array $fieldNames): AbstractModel
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);
        return new LineItem($fieldValues['tao_uri'], $fieldValues['title'], $fieldValues['infrastructure_id'], $fieldValues['start_date_time'], $fieldValues['end_date_time']);
    }
}