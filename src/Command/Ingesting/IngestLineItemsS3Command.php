<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\SourceSpecific\S3SourceSpecificTrait;

class IngestLineItemsS3Command extends AbstractIngestLineItemsCommand
{
    use S3SourceSpecificTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tao:s3-ingest:line-items');
        parent::configure();
    }
}