<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Model\ModelInterface;

abstract class AbstractRowToModelMapper
{
    protected function mapFileLineByFieldNames(array $row, array $fieldNames): array
    {
        $fieldValues = [];

        $numberOfLineElement = 0;
        foreach ($fieldNames as $fieldName) {
            $fieldValues[$fieldName] = array_key_exists($numberOfLineElement, $row) ? $row[$numberOfLineElement] : null;
            $numberOfLineElement++;
        }

        return $fieldValues;
    }

    abstract public function map(array $row, array $fieldNames): ModelInterface;
}