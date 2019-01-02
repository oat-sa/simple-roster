<?php

namespace App\Ingesting\RowToModelMapper;

use App\Model\User;

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

        /** @var User $user */
        $user = User::createFromArray($fieldValues);

        $assignments = $fieldValues['assignments'];
        unset($fieldValues['assignments']);
        $user->addAssignments($assignments);

        return $user;
    }
}