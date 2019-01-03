<?php

namespace App\Ingesting\RowToModelMapper;

class RowToModelMapper
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

    public function map(array $row, array $fieldNames, string $modelClass)
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);
        return $modelClass::createFromArray($fieldValues);
    }
}