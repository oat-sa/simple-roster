<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\SourceSpecific\S3SourceSpecificTrait;

class IngestUsersAndAssignmentsS3Command extends AbstractIngestUsersAndAssignmentsCommand
{
    use S3SourceSpecificTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tao:s3-ingest:user-and-assignments');
        parent::configure();
    }
}