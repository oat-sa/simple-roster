<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\IngestingException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Ingester\AbstractIngester;
use App\Ingesting\Ingester\IngesterInterface;
use App\Ingesting\Source\SourceInterface;
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
    private $io;

    /**
     * @var AbstractIngester[]
     */
    private $ingesters;

    /**
     * @var AbstractIngester
     */
    private $currentIngester;

    public function __construct(iterable $ingesters)
    {
        parent::__construct();

        $this->ingesters = $ingesters;
    }

    /**
     * Sets command name, input and metadata
     */
    protected function configure(): void
    {
        $this->addOption('data-type', 't', InputOption::VALUE_REQUIRED, 'Type of entity needed to be ingested');
        $this->addOption('wet-run', 'w', InputOption::VALUE_NONE, 'Data will be saved in storage');
        $this->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'CSV delimiter used in file ("," or "; normally)', ',');

        $this->setHelp(<<<HELP
The <info>%command.name%</info> command imports entities from the provided file to the database:
As an input a simple CSV file can be used or a file hosted on S3.
HELP
        );
    }

    abstract protected function getSource(InputInterface $inputOptions): SourceInterface;

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
        $wetRun = $input->getOption('wet-run') !== false;

        try {
            if (!in_array($input->getOption('data-type'), [IngesterInterface::TYPE_LINE_ITEM, IngesterInterface::TYPE_USER_AND_ASSIGNMENT, IngesterInterface::TYPE_INFRASTRUCTURE], true)) {
                $this->io->error('Data type not provided or wrong. Set one of the followings: '. implode(', ', [IngesterInterface::TYPE_LINE_ITEM, IngesterInterface::TYPE_USER_AND_ASSIGNMENT, IngesterInterface::TYPE_INFRASTRUCTURE]));
                exit(1);
            }

            $this->setUpIngester($input->getOption('data-type'));

            $source = $this->getSource($input);
            $result = $this->currentIngester->ingest($source, $wetRun);

            if ($wetRun) {
                $this->io->success(sprintf('Data has been ingested successfully.'));
            } else {
                $this->io->success(sprintf('DRY RUN! Data ingestion imitated successfully.'));
            }

            $alreadyExistingRowsCount = $result['alreadyExistingRowsCount'] ?? 0;
            $rowsAdded = $result['rowsAdded'] ?? 0;

            $messageOnUpdated = $this->currentIngester->isUpdateMode() ? 'updated' : 'were skipped as they already existed';
            $this->io->success(sprintf('%d records created, %d records %s.', $rowsAdded, $alreadyExistingRowsCount, $messageOnUpdated));
        } catch (InputOptionException $e) {
            $this->io->error(sprintf('Bad input parameters: %s', $e->getMessage()));
            return;
        } catch (FileLineIsInvalidException $e) {
            $this->io->error(sprintf('Ingestion terminated: the line %d is invalid: "%s"', $e->getLineNumber(), $e->getMessage()));
            return;
        } catch (IngestingException $e) {
            $this->io->error(sprintf('Error: %s', $e->getMessage()));
            return;
        } catch (\Exception $e) {
            $this->io->error(sprintf('Unknown error: %s', $e->getMessage()));
            return;
        }
    }

    /**
     * @param string $dataType
     * @throws InputOptionException
     */
    public function setUpIngester(string $dataType): void
    {
        foreach ($this->ingesters as $ingester) {
            if ($ingester->getType() === $dataType) {
                $this->currentIngester = $ingester;
                break;
            }
        }

        if ($this->currentIngester === null) {
            throw new InputOptionException('Unacceptable data type');
        }
    }
}