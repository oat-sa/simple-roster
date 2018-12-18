<?php

namespace App\Command\Ingesting;

use App\Entity\Entity;
use App\Entity\LineItem;

class IngestLineItemsCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('ingest-line-items')
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
     * {@inheritdoc}
     */
    protected function buildEntity(array $fields): Entity
    {
        return new LineItem($fields);
    }

    /**
     * @param Entity $entity
     * @throws \Exception
     */
    protected function validateEntity(Entity $entity): void
    {
        parent::validateEntity($entity);

        $infrastructureId = $entity->getData()['infrastructure_id'];

        $existingInfrastructure = $this->storage->read('infrastructures', ['id' => $infrastructureId]);

        if ($existingInfrastructure === null) {
            throw new \Exception(sprintf('Infrastructure with id "%s" not found', $infrastructureId));
        }
    }
}