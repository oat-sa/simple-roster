<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Command\Ingesting\SourceSpecific\LocalFileSourceSpecificTrait;

class IngestInfrastructuresLocalCommand extends AbstractIngestInfrastructuresCommand
{
    use LocalFileSourceSpecificTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tao:local-ingest:infrastructures');
        parent::configure();
    }
}