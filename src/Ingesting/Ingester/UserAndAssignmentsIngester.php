<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Model\ModelInterface;

class UserAndAssignmentsIngester extends AbstractIngester
{
    /**
     * {@inheritdoc}
     */
    protected $updateMode = true;

    public function getType(): string
    {
        return self::TYPE_USER_AND_ASSIGNMENT;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): ModelInterface
    {
        return $this->rowToModelMapper->map($row, ['username', 'password']);
    }
}