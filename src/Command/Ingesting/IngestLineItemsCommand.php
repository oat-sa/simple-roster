<?php

namespace App\Command\Ingesting;

use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\Model\Model;
use App\Model\LineItem;
use App\Model\Storage\InfrastructureStorage;
use App\Model\Storage\LineItemStorage;
use App\S3\S3ClientFactory;

class IngestLineItemsCommand extends AbstractIngestCommand
{
    /**
     * @var InfrastructureStorage
     */
    private $infrastructureStorage;

    public function __construct(LineItemStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, RowToModelMapper $rowToModelMapper, InfrastructureStorage $infrastructureStorage)
    {
        parent::__construct($modelStorage, $s3ClientFactory, $sourceFactory, $rowToModelMapper);

        $this->infrastructureStorage = $infrastructureStorage;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tao:ingest:line-items')
            ->setDescription('Import a list of line items')
            ->setHelp($this->getHelpHeader('line items') . <<<'HELP'
CSV fields: 
<info>tao_uri</info> string, required <comment>must be unique</comment>
<info>title</info> string, required
<info>infrastructure_id</info> string, required <comment>infrastructure must be already ingested</comment>
<info>start_date_time</info> string, optional
<info>end_date_time</info> string, optional
HELP
            );
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(): array
    {
        return ['tao_uri', 'title', 'infrastructure_id', 'start_date_time', 'end_date_time'];
    }

    /**
     * @param LineItem $entity
     * @throws \Exception
     */
    protected function validateEntity(Model $entity): void
    {
        parent::validateEntity($entity);

        $infrastructureId = $entity->getInfrastructureId();

        $existingInfrastructure = $this->infrastructureStorage->read($infrastructureId);

        if ($existingInfrastructure === null) {
            throw new \Exception(sprintf('Infrastructure with id "%s" not found', $infrastructureId));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getModelClass()
    {
        return LineItem::class;
    }
}