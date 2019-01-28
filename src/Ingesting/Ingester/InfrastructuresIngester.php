<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Model\ModelInterface;

class InfrastructuresIngester extends AbstractIngester
{
    public function getType(): string
    {
        return self::TYPE_INFRASTRUCTURE;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): ModelInterface
    {
        return $this->rowToModelMapper->map($row,
            ['id', 'lti_director_link', 'key', 'secret']
        );
    }
}