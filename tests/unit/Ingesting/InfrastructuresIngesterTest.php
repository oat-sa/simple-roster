<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Ingester\InfrastructuresIngester;
use App\Ingesting\RowToModelMapper\InfrastructureRowToModelMapper;
use App\Validation\ModelValidator;

class InfrastructuresIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        parent::setUp();

        $this->rowToModelMapper = $this->createMock(InfrastructureRowToModelMapper::class);
        $this->ingester = new InfrastructuresIngester($this->itemManager, $this->rowToModelMapper);
    }

    public function itemsProvider()
    {
        return [[
            [
                ['id', 'lti_director_link', 'key', 'secret'],
                ['id_2', 'lti_director_link', 'key', 'secret'],
                ['id_3', 'lti_director_link', 'key', 'secret'],
            ]
        ]];
    }
}