<?php declare(strict_types=1);

namespace App\Command\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Exception\IngestingException;
use App\Ingesting\Exception\InputOptionException;
use App\Ingesting\Ingester\AbstractIngester;
use App\Ingesting\Ingester\InfrastructuresIngester;
use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\Ingester\UserAndAssignmentsIngester;
use App\Ingesting\Source\SourceInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractIngestCommand extends ContainerAwareCommand
{
    private const TYPE_USER_AND_ASSIGNMENT = 'users-assignments';
    private const TYPE_INFRASTRUCTURE = 'infrastructures';
    private const TYPE_LINE_ITEM = 'line-items';

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
        $dryRun = $input->getOption('wet-run') === false;

        try {
            if (!in_array($input->getOption('data-type'), [self::TYPE_LINE_ITEM, self::TYPE_USER_AND_ASSIGNMENT, self::TYPE_INFRASTRUCTURE], true)) {
                $this->io->error('Data type not provided or wrong. Set one of the followings: '. implode(', ', [self::TYPE_LINE_ITEM, self::TYPE_USER_AND_ASSIGNMENT, self::TYPE_INFRASTRUCTURE]));
                exit(1);
            }

            $this->setUpIngester($input->getOption('data-type'));

            $source = $this->getSource($input);
            $result = $this->ingester->ingest($source, $dryRun);

            if ($dryRun) {
                $this->io->success(sprintf('DRY RUN! Data ingestion imitated successfully.'));
            } else {
                $this->io->success(sprintf('Data has been ingested successfully.'));
            }

            $alreadyExistingRowsCount = $result['alreadyExistingRowsCount'] ?? 0;
            $rowsAdded = $result['rowsAdded'] ?? 0;

            $messageOnUpdated = $this->ingester->isUpdateMode() ? 'updated' : 'were skipped as they already existed';
            $this->io->success(sprintf('%d records created, %d records %s.', $rowsAdded, $alreadyExistingRowsCount, $messageOnUpdated));
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
    }

    /**
     * @param string $dataType
     * @throws InputOptionException
     */
    public function setUpIngester(string $dataType): void
    {
        switch ($dataType) {
            case self::TYPE_USER_AND_ASSIGNMENT:
                $ingesterClass = UserAndAssignmentsIngester::class;
                break;
            case self::TYPE_INFRASTRUCTURE:
                $ingesterClass = InfrastructuresIngester::class;
                break;
            case self::TYPE_LINE_ITEM:
                $ingesterClass = LineItemsIngester::class;
                break;
            default:
                throw new InputOptionException('Unacceptable data type');
        }

        $this->ingester = $this->getContainer()->get($ingesterClass);
    }
}