<?php

namespace App\Command\Ingesting;

use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\SourceFactory;
use App\Model\Infrastructure;
use App\Model\Model;
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

    /**
     * {@inheritdoc}
     */
    protected function convertRowToModel(array $row): Model
    {
        return $this->rowToModelMapper->map($row,
            ['id', 'lti_director_link', 'key', 'secret'],
            Infrastructure::class
        );
    }
}