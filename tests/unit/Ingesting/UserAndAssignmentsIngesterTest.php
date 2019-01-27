<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Ingester\UserAndAssignmentsIngester;
use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\ModelManager\UserManager;
use App\Validation\UserValidator;

class UserAndAssignmentsIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        $this->modelManager = $this->createMock(UserManager::class);
        $this->rowToModelMapper = $this->createMock(UserRowToModelMapper::class);
        $this->modelValidator = $this->createMock(UserValidator::class);
        $this->ingester = new UserAndAssignmentsIngester($this->modelManager, $this->rowToModelMapper, $this->modelValidator);

        parent::setUp();
    }

    public function itemsProvider()
    {
        return [[
            [
                ['username', 'password', 'assignment_1'],
                ['username_2', 'password', 'assignment_1', 'assignment_2'],
            ]
        ]];
    }
}