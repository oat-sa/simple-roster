<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\SourceSpecific\LocalFileSourceSpecificTrait;

class IngestUsersAndAssignmentsLocalCommand extends AbstractIngestUsersAndAssignmentsCommand
{
    use LocalFileSourceSpecificTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tao:local-ingest:user-and-assignments');
        parent::configure();
    }
}