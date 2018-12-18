<?php

namespace App\Command\Ingesting;

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
            ->setDescription('Import a list of users')
            ->setHelp($this->getHelpHeader('users') . <<<'HELP'
CSV fields: 
<info>user login</info> string <comment>must be unique</comment>
<info>user password</info> string <comment>plain</comment>

Example:
"Bob";"qwerty"
HELP
            );

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
    protected function buildEntity(array $fieldsValues): Entity
    {
        return new User($fieldsValues);
    }
}