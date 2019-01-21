<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\InfrastructuresIngester;
use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\RowToModelMapper\InfrastructureRowToModelMapper;
use App\Ingesting\RowToModelMapper\LineItemRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\ModelInterface;
use App\ModelManager\InfrastructureManager;
use App\ModelManager\LineItemManager;
use App\Validation\ModelValidator;
use PHPUnit\Framework\TestCase;

class InfrastructuresIngesterTest extends AbstractIngesterTest
{
    public function setUp()
    {
        $this->modelManager = $this->createMock(InfrastructureManager::class);
        $this->rowToModelMapper = $this->createMock(InfrastructureRowToModelMapper::class);
        $this->modelValidator = $this->createMock(ModelValidator::class);
        $this->ingester = new InfrastructuresIngester($this->modelManager, $this->rowToModelMapper, $this->modelValidator);

        parent::setUp();
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