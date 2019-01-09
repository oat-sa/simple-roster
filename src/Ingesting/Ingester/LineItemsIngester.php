<?php

namespace App\Ingesting\Ingester;

use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Model\AbstractModel;
use App\Model\LineItem;
use App\Model\Storage\InfrastructureStorage;
use App\Model\Storage\LineItemStorage;
use App\S3\S3ClientFactory;

class LineItemsIngester extends AbstractIngester
{
    /**
     * @var InfrastructureStorage
     */
    private $infrastructureStorage;

    public function __construct(LineItemStorage $modelStorage, RowToModelMapper $rowToModelMapper, InfrastructureStorage $infrastructureStorage)
    {
        parent::__construct($modelStorage, $rowToModelMapper);

        $this->infrastructureStorage = $infrastructureStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): AbstractModel
    {
        return $this->rowToModelMapper->map($row,
            ['tao_uri', 'title', 'infrastructure_id', 'start_date_time', 'end_date_time'],
            LineItem::class
        );
    }

    /**
     * @param LineItem $entity
     * @throws \Exception
     */
    protected function validateEntity(AbstractModel $entity): void
    {
        parent::validateEntity($entity);

        $infrastructureId = $entity->getInfrastructureId();

        $existingInfrastructure = $this->infrastructureStorage->read($infrastructureId);

        if ($existingInfrastructure === null) {
            throw new \Exception(sprintf('Infrastructure with id "%s" not found', $infrastructureId));
        }
    }
}