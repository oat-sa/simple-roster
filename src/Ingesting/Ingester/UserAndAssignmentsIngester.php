<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\Model\AbstractModel;
use App\Model\Storage\UserStorage;
use App\Model\User;
use App\S3\S3ClientFactory;

class UserAndAssignmentsIngester extends AbstractIngester
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    public function __construct(UserStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, UserRowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $s3ClientFactory, $sourceFactory, $rowToModelMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row, ['login', 'password'], User::class);
    }
}