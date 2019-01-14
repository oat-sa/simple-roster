<?php declare(strict_types=1);

namespace App\Ingesting\RowToModelMapper;

use App\Model\ModelInterface;
use App\Model\Infrastructure;

class InfrastructureRowToModelMapper extends AbstractRowToModelMapper
{
    public function map(array $row, array $fieldNames): ModelInterface
    {
        $fieldValues = $this->mapFileLineByFieldNames($row, $fieldNames);
        return new Infrastructure($fieldValues['id'], $fieldValues['lti_director_link'], $fieldValues['key'], $fieldValues['secret']);
    }
}