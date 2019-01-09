<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\SourceSpecific\LocalFileSourceSpecificTrait;

class IngestLineItemsLocalCommand extends AbstractIngestLineItemsCommand
{
    use LocalFileSourceSpecificTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tao:local-ingest:line-items');
        parent::configure();
    }
}