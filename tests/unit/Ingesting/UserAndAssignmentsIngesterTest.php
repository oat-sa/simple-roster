<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\InfrastructuresIngester;
use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\Ingester\UserAndAssignmentsIngester;
use App\Ingesting\RowToModelMapper\InfrastructureRowToModelMapper;
use App\Ingesting\RowToModelMapper\LineItemRowToModelMapper;
use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\ModelInterface;
use App\ModelManager\InfrastructureManager;
use App\ModelManager\LineItemManager;
use App\ModelManager\UserManager;
use App\Validation\ModelValidator;
use PHPUnit\Framework\TestCase;

class UserAndAssignmentsIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        $this->modelManager = $this->createMock(UserManager::class);
        $this->rowToModelMapper = $this->createMock(UserRowToModelMapper::class);
        $this->modelValidator = $this->createMock(ModelValidator::class);
        $this->ingester = new UserAndAssignmentsIngester($this->modelManager, $this->rowToModelMapper, $this->modelValidator);

        parent::setUp();
    }

    public function itemsProvider()
    {
        return [[
            [
                ['login', 'password', 'assignment_1'],
                ['login_2', 'password', 'assignment_1', 'assignment_2'],
            ]
        ]];
    }
}