<?php

namespace App\Command\Ingesting;

use App\Ingesting\Ingester\LineItemsIngester;

class IngestLineItemsCommand extends AbstractIngestCommand
{
    public function __construct(LineItemsIngester $ingester)
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
<info>tao_uri</info> string, required <comment>must be unique</comment>
<info>title</info> string, required
<info>infrastructure_id</info> string, required <comment>infrastructure must be already ingested</comment>
<info>start_date_time</info> string, optional
<info>end_date_time</info> string, optional
HELP;

        $this
            ->setName('tao:ingest:line-items')
            ->setDescription('Import a list of line items')
            ->setHelp($this->getHelpHeader('line items') . $help);
        parent::configure();
    }
}