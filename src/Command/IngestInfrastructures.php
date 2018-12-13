<?php

namespace App\Command;

use App\Entity\Entity;
use App\Entity\Infrastructure;

class IngestInfrastructures extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('ingest-infrastructures')
            ->setDescription('TBD')
            ->setHelp('TBD');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(): array
    {
        return ['id', 'lti_director_link', 'key', 'secret'];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildEntity(array $fields): Entity
    {
        return new Infrastructure($fields);
    }
}