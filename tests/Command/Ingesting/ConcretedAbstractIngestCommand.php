<?php

namespace App\Tests\Command\Ingesting;

use App\Command\Ingesting\AbstractIngestCommand;
use App\Entity\Entity;

class ConcretedAbstractIngestCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getFields(): array
    {
        return ['name', 'mandatory_prop_1', 'mandatory_prop_2', 'optional_prop_1'];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildEntity(array $fieldsValues): Entity
    {
        return new ExampleEntity($fieldsValues);
    }
}