<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Ingester\LineItemsIngester;
use App\S3\S3ClientFactory;

abstract class AbstractIngestLineItemsCommand extends AbstractIngestCommand
{
    public function __construct(LineItemsIngester $ingester, S3ClientFactory $s3ClientFactory)
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
<info>tao_uri</info> string, required <comment>must be unique</comment>
<info>title</info> string, required
<info>infrastructure_id</info> string, required <comment>infrastructure must be already ingested</comment>
<info>start_date_time</info> string, optional
<info>end_date_time</info> string, optional
HELP;

        $this
            ->setDescription('Import a list of line items')
            ->setHelp($this->getHelpHeader('line items') . $help);
        parent::configure();
    }
}