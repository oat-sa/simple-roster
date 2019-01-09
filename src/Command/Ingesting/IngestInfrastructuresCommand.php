<?php

namespace App\Command\Ingesting;

use App\Ingesting\Ingester\InfrastructuresIngester;

class IngestInfrastructuresCommand extends AbstractIngestCommand
{
    public function __construct(InfrastructuresIngester $ingester)
    {
        parent::__construct($ingester);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $help = <<<'HELP'
CSV fields: 
<info>id</info> string, required <comment>must be unique</comment>
<info>lti_director_link</info> string, required
<info>key</info> string, required
<info>secret</info> string, required
HELP;

        $this
            ->setName('tao:ingest:infrastructures')
            ->setDescription('Import a list of infrastructures')
            ->setHelp($this->getHelpHeader('infrastructures') . $help);
        parent::configure();
    }
}