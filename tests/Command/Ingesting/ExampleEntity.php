<?php

namespace App\Tests\Command\Ingesting;

use App\Entity\Entity;

class ExampleEntity extends Entity
{
    protected $requiredProperties = ['mandatory_prop_1', 'mandatory_prop_2'];

    public function getTable(): string
    {
        return 'example_table';
    }

    public function getKey()
    {
        return 'name';
    }
}