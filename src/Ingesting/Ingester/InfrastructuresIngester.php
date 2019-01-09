<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\Model\AbstractModel;
use App\Model\Infrastructure;
use App\Model\Storage\InfrastructureStorage;
use App\S3\S3ClientFactory;

class InfrastructuresIngester extends AbstractIngester
{
    public function __construct(InfrastructureStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, RowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $s3ClientFactory, $sourceFactory, $rowToModelMapper);
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