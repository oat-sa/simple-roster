<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\IngestingException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Ingester\AbstractIngester;
use App\Ingesting\Source\SourceInterface;
use App\S3\S3ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractIngestCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var AbstractIngester
     */
    private $ingester;

    /**
     * @var S3ClientInterface
     */
    private $s3Client;

    public function __construct(AbstractIngester $ingester, S3ClientInterface $s3Client)
    {
        $this->ingester = $ingester;
        $this->s3Client = $s3Client;

        parent::__construct();
    }

    /**
     * Configure source specific options
     *
     * @return void
     */
    abstract protected function addSourceOptions(): void;

    /**
     * Sets command name, input and metadata
     */
    protected function configure(): void
    {
        $this->addSourceOptions();
    }

    abstract protected function getSource(array $inputOptions): SourceInterface;

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
            $source = $this->getSource($input->getOptions());
            $result = $this->ingester->ingest($source);

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

    /**
     * For S3SourceSpecificTrait
     *
     * @return S3ClientInterface
     */
    protected function getS3Client(): S3ClientInterface
    {
        return $this->s3Client;
    }
}