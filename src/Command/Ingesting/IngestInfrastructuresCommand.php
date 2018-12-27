<?php

namespace App\Command\Ingesting;

use App\Entity\Entity;
use App\Entity\Infrastructure;

class IngestInfrastructuresCommand extends AbstractIngestCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('tao:ingest:infrastructures')
            ->setDescription('Import a list of infrastructures')
            ->setHelp($this->getHelpHeader('infrastructures') . <<<'HELP'
CSV fields: 
<info>id</info> string, required <comment>must be unique</comment>
<info>lti_director_link</info> string, required
<info>key</info> string, required
<info>secret</info> string, required
HELP
            );
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
    protected function buildEntity(array $fieldsValues): Entity
    {
        return new Infrastructure($fieldsValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass()
    {
        return Infrastructure::class;
    }
}