<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Source\S3CsvSource;
use App\Ingesting\Source\SourceInterface;
use App\S3\S3ClientInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class IngestS3Command extends AbstractIngestCommand
{
    protected static $defaultName = 'roster:s3-ingest';

    /**
     * @var S3ClientInterface
     */
    protected $s3Client;

    public function __construct(S3ClientInterface $s3Client)
    {
        $this->s3Client = $s3Client;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('roster:s3-ingest')
            ->addArgument('s3_bucket', InputArgument::REQUIRED, 'Name of a S3 bucket')
            ->addArgument('s3_object', InputArgument::REQUIRED, 'Key of a S3 object')
            ->addOption('s3_access_key', null, InputOption::VALUE_REQUIRED, 'AWS access key')
            ->addOption('s3_secret', null, InputOption::VALUE_REQUIRED, 'AWS secret key');

        parent::configure();
    }

    protected function getSource(InputInterface $input): SourceInterface
    {
        if (null !== $input->getOption('s3_access_key') && null !== $input->getOption('s3_secret')) {
            $this->s3Client->connect($input->getOption('s3_access_key'), $input->getOption('s3_secret'));
        }

        return new S3CsvSource($this->s3Client, $input->getArgument('s3_bucket'), $input->getArgument('s3_object'), $input->getOption('delimiter'));
    }
}