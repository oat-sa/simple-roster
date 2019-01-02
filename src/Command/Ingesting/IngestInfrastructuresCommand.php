<?php

namespace App\Command\Ingesting;

use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\Model\Infrastructure;
use App\Model\Storage\InfrastructureStorage;
use App\S3\S3ClientFactory;

class IngestInfrastructuresCommand extends AbstractIngestCommand
{
    public function __construct(InfrastructureStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, RowToModelMapper $rowToModelMapper)
    {
        parent::__construct($modelStorage, $s3ClientFactory, $sourceFactory, $rowToModelMapper);
    }

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
    protected function getModelClass()
    {
        return Infrastructure::class;
    }
}