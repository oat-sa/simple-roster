<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Ingester\InfrastructuresIngester;
use App\S3\S3ClientFactory;

abstract class AbstractIngestInfrastructuresCommand extends AbstractIngestCommand
{
    public function __construct(InfrastructuresIngester $ingester, S3ClientFactory $s3ClientFactory)
    {
        parent::__construct($ingester, $s3ClientFactory);
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
            ->setDescription('Import a list of infrastructures')
            ->setHelp($this->getHelpHeader('infrastructures') . $help);
        parent::configure();
    }
}