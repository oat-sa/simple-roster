<?php

namespace App\Command;

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
            ->setDescription('TBD')
            ->setHelp('TBD');

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
}