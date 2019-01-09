<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Model\AbstractModel;
use App\Model\Infrastructure;
use App\Model\Storage\InfrastructureStorage;

class InfrastructuresIngester extends AbstractIngester
{
    public function __construct(InfrastructureStorage $modelStorage, RowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $rowToModelMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row,
            ['id', 'lti_director_link', 'key', 'secret'],
            Infrastructure::class
        );
    }
}