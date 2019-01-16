<?php declare(strict_types=1);

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\InfrastructureRowToModelMapper;
use App\Model\ModelInterface;
use App\Model\Storage\InfrastructureManager;
use App\Model\Validation\ModelValidator;

class InfrastructuresIngester extends AbstractIngester
{
    public function __construct(InfrastructureManager $modelStorage, InfrastructureRowToModelMapper $rowToModelMapper, ModelValidator $validator)
    {
        parent::__construct($modelStorage, $rowToModelMapper, $validator);
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