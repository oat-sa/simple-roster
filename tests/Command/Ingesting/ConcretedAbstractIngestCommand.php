<?php

namespace App\Tests\Command\Ingesting;

use App\Command\Ingesting\AbstractIngestCommand;
use App\Model\AbstractModel;

class ConcretedAbstractIngestCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row,
            ['name', 'mandatory_prop_1', 'mandatory_prop_2', 'optional_prop_1'],
            ExampleAbstractModel::class
        );
    }
}