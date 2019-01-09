<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Model\AbstractModel;
use App\Model\Storage\UserStorage;
use App\Model\User;

class UserAndAssignmentsIngester extends AbstractIngester
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    public function __construct(UserStorage $modelStorage, UserRowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $rowToModelMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row, ['login', 'password'], User::class);
    }
}