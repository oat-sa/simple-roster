<?php

namespace App\Ingesting\RowToModelMapper;

class UserRowToModelMapper extends RowToModelMapper
{
    public function map(array $row, array $fieldNames, string $modelClass)
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);

        // collect the remaining elements of line to the single 'assignment' field
        $fieldCount = count($fieldNames);
        $fieldValues['assignments'] = [];
        for ($i = $fieldCount; $i < count($row); $i++) {
            $fieldValues['assignments'][] = $row[$i];
        }

        $user = new \App\Entity\User($row);

        $assignments = $fieldValues['assignments'];
        unset($fieldValues['assignments']);
        $user->addAssignments($assignments);

        return $user;
    }
}