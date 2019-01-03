<?php

namespace App\Command\Ingesting;

use App\Ingesting\Exception\IngestingException;
use App\Ingesting\Exception\InputOptionException;
use App\Model\Model;
use App\Model\Storage\ModelStorage;
use App\Model\Validation\ValidationException;
use App\Ingesting\RowToModelMapper\RowToModelMapper;
use App\Ingesting\Source\Source;
use App\Ingesting\Source\SourceFactory;
use App\S3\S3ClientFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractIngestCommand extends Command
{
    /** @var SymfonyStyle */
    protected $io;

    /**
     * @var ModelStorage
     */
    protected $modelStorage;

    /**
     * @var S3ClientFactory
     */
    protected $s3ClientFactory;

    /**
     * @var SourceFactory
     */
    protected $sourceFactory;

    /**
     * @var RowToModelMapper
     */
    protected $rowToModelMapper;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(ModelStorage $modelStorage, S3ClientFactory $s3ClientFactory, SourceFactory $sourceFactory, RowToModelMapper $rowToModelMapper)
    {
        parent::__construct();

        $this->modelStorage = $modelStorage;
        $this->s3ClientFactory = $s3ClientFactory;
        $this->sourceFactory = $sourceFactory;
        $this->rowToModelMapper = $rowToModelMapper;
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
     * @param String[] $row
     * @return Model
     */
    abstract protected function convertRowToModel(array $row): Model;

    /**
     * @param InputInterface $input
     * @return Source
     * @throws InputOptionException
     */
    private function detectSource(InputInterface $input): Source
    {
        $accessParameters = [];
        foreach ($this->sourceFactory->getSupportedAccessParameters() as $parameterName) {
            $accessParameters[$parameterName] = $input->hasOption($parameterName) ? $input->getOption($parameterName) : null;
        }
        $accessParameters['s3_client_factory'] = $this->s3ClientFactory;
        try {
            return $this->sourceFactory->createSource($accessParameters);
        } catch (\Exception $e) {
            throw new InputOptionException($e->getMessage());
        }
    }

    /**
     * @inheritdoc
     */
    public function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param Model $entity
     * @throws ValidationException
     */
    protected function validateEntity(Model $entity): void
    {
        $entity->validate();
    }

    /**
     * Checks if the record with same primary key already exists
     *
     * @param Model $entity
     * @return bool
     */
    protected function checkIfExists(Model $entity): bool
    {
        return $this->modelStorage->read($this->modelStorage->getKey($entity)) !== null;
    }

    /**
     * @param InputInterface $input
     * @return array
     * @throws IngestingException
     * @throws InputOptionException
     */
    public function executeUnformatted(InputInterface $input): array
    {
        $alreadyExistingRowsCount = $rowsAdded = 0;

        $lineNumber = 0;
        foreach ($this->detectSource($input)->iterateThroughLines() as $line) {
            $lineNumber++;
            $entity = $this->convertRowToModel($line);
            try {
                $this->validateEntity($entity);
            } catch (ValidationException $e) {
                $this->io->warning(sprintf('The process has been terminated because the line %d of the file is invalid:', $lineNumber));
                $this->io->error(sprintf('%s', $e->getMessage()));
                return [];
            }

            if ($this->checkIfExists($entity)) {
                $alreadyExistingRowsCount++;
                if (!$this->updateMode) {
                    continue;
                }
            } else {
                if (!$this->updateMode) {
                    $rowsAdded++;
                }
            }

            $this->modelStorage->insert($this->modelStorage->getKey($entity), $entity);
        }

        return [
            'rowsAdded' => $rowsAdded,
            'alreadyExistingRowsCount' => $alreadyExistingRowsCount,
        ];
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
            $result = $this->executeUnformatted($input);

            $this->io->success(sprintf('Data has been ingested successfully.'));
        } catch (InputOptionException $e) {
            $this->io->error(sprintf('Bad input parameters: %s', $e->getMessage()));
        } catch (IngestingException $e) {
            $this->io->error(sprintf('Error: %s', $e->getMessage()));
        } catch (\Exception $e) {
            $this->io->error(sprintf('Unknown error: %s', $e->getMessage()));
        }

        $alreadyExistingRowsCount = $result['alreadyExistingRowsCount'] ?? 0;
        $rowsAdded = $result['rowsAdded'] ?? 0;

        $messageOnUpdated = $this->updateMode ? 'updated' : 'were skipped as they already existed';
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