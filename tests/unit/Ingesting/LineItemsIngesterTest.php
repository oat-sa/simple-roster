<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\RowToModelMapper\LineItemRowToModelMapper;

class LineItemsIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        parent::setUp();

        $this->rowToModelMapper = $this->createMock(LineItemRowToModelMapper::class);
        $this->ingester = new LineItemsIngester($this->itemManager, $this->rowToModelMapper);
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