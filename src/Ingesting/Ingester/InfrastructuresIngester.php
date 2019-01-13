<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\InfrastructureRowToModelMapper;
use App\Model\AbstractModel;
use App\Model\Storage\InfrastructureStorage;

class InfrastructuresIngester extends AbstractIngester
{
    public function __construct(InfrastructureStorage $modelStorage, InfrastructureRowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $rowToModelMapper);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row,
            ['id', 'lti_director_link', 'key', 'secret']
        );
    }
}