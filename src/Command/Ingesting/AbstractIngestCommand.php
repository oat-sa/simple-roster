<?php

namespace App\Command\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\IngestingException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Ingester\AbstractIngester;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractIngestCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var AbstractIngester
     */
    private $ingester;

    public function __construct(AbstractIngester $ingester)
    {
        $this->ingester = $ingester;

        parent::__construct();
    }

    /**
     * Sets command name, input and metadata
     */
    protected function configure(): void
    {
        $this
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The filename with CSV data')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'CSV delimiter used in file ("," or "; normally)', ',')
            ->addOption('s3_bucket', null, InputOption::VALUE_OPTIONAL, 'Name of a S3 bucket')
            ->addOption('s3_object', null, InputOption::VALUE_OPTIONAL, 'Key of a S3 object')
            ->addOption('s3_region', null, InputOption::VALUE_OPTIONAL, 'Region specified for S3 bucket')
            ->addOption('s3_access_key', null, InputOption::VALUE_OPTIONAL, 'AWS access key')
            ->addOption('s3_secret', null, InputOption::VALUE_OPTIONAL, 'AWS secret key');
    }

    /**
     * @inheritdoc
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Entry point to the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output): void
    {
        try {
            $result = $this->ingester->ingest($input->getOptions());

            $this->io->success(sprintf('Data has been ingested successfully.'));
        } catch (InputOptionException $e) {
            $this->io->error(sprintf('Bad input parameters: %s', $e->getMessage()));
        } catch (FileLineIsInvalidException $e) {
            $this->io->warning(sprintf('The process has been terminated because the line %d of the file is invalid:', $e->getLineNumber()));
            $this->io->error(sprintf('%s', $e->getMessage()));
        } catch (IngestingException $e) {
            $this->io->error(sprintf('Error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            $this->io->error(sprintf('Unknown error: %s', $e->getMessage()));
        }

        $alreadyExistingRowsCount = $result['alreadyExistingRowsCount'] ?? 0;
        $rowsAdded = $result['rowsAdded'] ?? 0;

        $messageOnUpdated = $this->ingester->isUpdateMode() ? 'updated' : 'were skipped as they already existed';
        $this->io->write(sprintf('%d records created, %d records %s.', $rowsAdded, $alreadyExistingRowsCount, $messageOnUpdated));
    }

    /**
     * Gets generic help common to all ingesting commands
     *
     * @param string $entityName
     * @return string
     */
    public function getHelpHeader(string $entityName): string
    {
        return <<<HELP
The <info>%command.name%</info> command imports {$entityName} from the provided file to the database:
As an input a simple CSV file can be used or a file hosted on S3.


HELP;
    }
}