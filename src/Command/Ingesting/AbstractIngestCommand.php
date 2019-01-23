<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\IngestingException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Ingester\AbstractIngester;
use App\Ingesting\Ingester\InfrastructuresIngester;
use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\Ingester\RepeatedAssignmentIngester;
use App\Ingesting\Ingester\UserAndAssignmentsIngester;
use App\Ingesting\Source\SourceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractIngestCommand extends ContainerAwareCommand
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
     * Sets command name, input and metadata
     */
    protected function configure(): void
    {
        $this->addOption('data-type', null, InputOption::VALUE_REQUIRED, 'Type of entity needed to be ingested');
        $this->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Do not write any data', false);

        $this->setHelp(<<<HELP
The <info>%command.name%</info> command imports entities from the provided file to the database:
As an input a simple CSV file can be used or a file hosted on S3.
HELP
        );
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
        $inputOptions = $input->getOptions();
        $dryRun = $input->getOption('dry-run') !== false;

        try {
            $this->setUpIngester($input->getOption('data-type'));

            $source = $this->getSource($inputOptions);
            $result = $this->ingester->ingest($source, $dryRun);

            if ($dryRun) {
                $this->io->success(sprintf('DRY RUN! Data ingestion imitated successfully.'));
            } else {
                $this->io->success(sprintf('Data has been ingested successfully.'));
            }
        } catch (InputOptionException $e) {
            $this->io->error(sprintf('Bad input parameters: %s', $e->getMessage()));
            return;
        } catch (FileLineIsInvalidException $e) {
            $this->io->warning(sprintf('The process has been terminated because the line %d of the file is invalid:', $e->getLineNumber()));
            $this->io->error(sprintf('%s', $e->getMessage()));
            return;
        } catch (IngestingException $e) {
            $this->io->error(sprintf('Error: %s', $e->getMessage()));
            return;
        } catch (\Exception $e) {
            $this->io->error(sprintf('Unknown error: %s', $e->getMessage()));
            return;
        }

        $alreadyExistingRowsCount = $result['alreadyExistingRowsCount'] ?? 0;
        $rowsAdded = $result['rowsAdded'] ?? 0;

        $messageOnUpdated = $this->ingester->isUpdateMode() ? 'updated' : 'were skipped as they already existed';
        $this->io->write(sprintf('%d records created, %d records %s.', $rowsAdded, $alreadyExistingRowsCount, $messageOnUpdated));
    }

    /**
     * @param string $dataType
     * @throws InputOptionException
     */
    public function setUpIngester(string $dataType): void
    {
        switch ($dataType) {
            case 'users-and-assignments':
                $ingesterClass = UserAndAssignmentsIngester::class;
                break;
            case 'infrastructures':
                $ingesterClass = InfrastructuresIngester::class;
                break;
            case 'line-items':
                $ingesterClass = LineItemsIngester::class;
                break;
            case 'repeat-assignments':
                $ingesterClass = RepeatedAssignmentIngester::class;
                break;
            default:
                throw new InputOptionException('Unacceptable data type');
        }

        $this->ingester = $this->getContainer()->get($ingesterClass);
    }
}