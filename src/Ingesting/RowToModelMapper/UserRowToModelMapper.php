<?php

namespace App\Ingesting\RowToModelMapper;

use App\Model\AbstractModel;
use App\Model\Assignment;
use App\Model\User;

class UserRowToModelMapper extends AbstractRowToModelMapper
{
    public function map(array $row, array $fieldNames): AbstractModel
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);

        // collect the remaining elements of line to the single 'assignment' field
        $fieldCount = count($fieldNames);
        $fieldValues['assignments'] = [];
        for ($i = $fieldCount; $i < count($row); $i++) {
            $fieldValues['assignments'][] = $row[$i];
        }

        $assignmentUris = $fieldValues['assignments'];
        $assignments = [];
        foreach ($assignmentUris as $assignmentUri) {
            $assignments[] = new Assignment($assignmentUri);
        }
        unset($fieldValues['assignments']);

        /** @var User $user */
        $user = new User($fieldValues['login'], $fieldValues['password']);

        $user->addAssignments($assignments);

        return $user;
    }
}