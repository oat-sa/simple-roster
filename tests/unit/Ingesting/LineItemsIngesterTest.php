<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\RowToModelMapper\LineItemRowToModelMapper;
use App\ModelManager\LineItemManager;
use App\Validation\LineItemValidator;

class LineItemsIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        $this->modelManager = $this->createMock(LineItemManager::class);
        $this->rowToModelMapper = $this->createMock(LineItemRowToModelMapper::class);
        $this->modelValidator = $this->createMock(LineItemValidator::class);
        $this->ingester = new LineItemsIngester($this->modelManager, $this->rowToModelMapper, $this->modelValidator);

        parent::setUp();
    }

    public function itemsProvider()
    {
        return [[
            [
                ['tao_uri', 'title', 'infrastructure_id', 'start_date_time', 'end_date_time'],
                ['tao_uri_2', 'title', 'infrastructure_id', 'start_date_time', 'end_date_time'],
            ]
        ]];
    }
}