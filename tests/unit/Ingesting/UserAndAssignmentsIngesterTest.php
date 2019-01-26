<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Ingester\UserAndAssignmentsIngester;
use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Validation\UserValidator;

class UserAndAssignmentsIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        parent::setUp();

        $this->rowToModelMapper = $this->createMock(UserRowToModelMapper::class);
        $this->ingester = new UserAndAssignmentsIngester($this->itemManager, $this->rowToModelMapper);
    }

    public function itemsProvider()
    {
        return [[
            [
                ['username', 'password', 'assignment_1'],
                ['username_1', 'password', 'assignment_1', 'assignment_2'],
            ]
        ]];
    }
}