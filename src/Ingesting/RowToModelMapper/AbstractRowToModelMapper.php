<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Model\ModelInterface;

abstract class AbstractRowToModelMapper
{
    protected function convertStringToDateTime(string $dateTimeString)
    {
         $result = \DateTime::createFromFormat('Y-m-d H:i:s', $dateTimeString);
         if ($result === false) {
             return null;
         }
         return $result;
    }

    protected function mapFileLineByFieldNames(array $row, array $fieldNames): array
    {
        $fieldValues = [];

        $numberOfLineElement = 0;
        foreach ($fieldNames as $fieldName) {
            $fieldValues[$fieldName] = $row[$numberOfLineElement] ?? null;
            $numberOfLineElement++;
        }

        return $fieldValues;
    }

    abstract public function map(array $row, array $fieldNames): ModelInterface;
}