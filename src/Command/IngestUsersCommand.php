<?php

namespace App\Command;

use App\Entity\Entity;
use App\Entity\User;

class IngestUsersCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('ingest-users')
            ->setDescription('TBD')
            ->setHelp('TBD');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFields(): array
    {
        return ['login', 'password'];
    }

    /**
     * {@inheritdoc}
     */
    protected function buildEntity(array $fields): Entity
    {
        return new User($fields);
    }
}