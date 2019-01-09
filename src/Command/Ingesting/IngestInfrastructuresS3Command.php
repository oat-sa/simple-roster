<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\SourceSpecific\S3SourceSpecificTrait;

class IngestInfrastructuresS3Command extends AbstractIngestInfrastructuresCommand
{
    use S3SourceSpecificTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tao:s3-ingest:infrastructures');
        parent::configure();
    }
}