<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\UserRowToModelMapper;
use App\Model\ModelInterface;
use App\Model\Storage\UserStorage;
use App\Model\User;
use App\Model\Validation\UserValidator;

class UserAndAssignmentsIngester extends AbstractIngester
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    public function __construct(UserStorage $modelStorage, UserRowToModelMapper $rowToModelMapper, UserValidator $validator)
    {
        parent::__construct($modelStorage, $rowToModelMapper, $validator);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): ModelInterface
    {
        return $this->rowToModelMapper->map($row, ['login', 'password']);
    }
}