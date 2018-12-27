<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\Exception\FileNotFoundException;
use App\Command\Ingesting\Exception\IngestingException;
use App\Command\Ingesting\Exception\InputOptionException;
use App\Command\Ingesting\Exception\S3AccessException;
use App\Entity\Entity;
use App\Entity\Validation\ValidationException;
use App\S3\S3ClientFactory;
use App\Storage\Storage;
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
     * @var Storage
     */
    protected $storage;

    /**
     * @var S3ClientFactory
     */
    protected $s3ClientFactory;

    /**
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(Storage $storage, S3ClientFactory $s3ClientFactory)
    {
        parent::__construct();

        $this->storage = $storage;
        $this->s3ClientFactory = $s3ClientFactory;
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
     * List of accepted fields
     *
     * @return array
     */
    abstract protected function getFields(): array;

    /**
     * Creates entity by fields values
     *
     * @param array $fieldsValues
     * @return Entity
     */
    abstract protected function buildEntity(array $fieldsValues): Entity;

    /**
     * @param InputInterface $input
     * @param string $optionName
     * @throws InputOptionException
     */
    private function checkAwsOption(InputInterface $input, string $optionName): void
    {
        if (!$input->getOption($optionName)) {
            throw new InputOptionException(sprintf('Option %s is not provided', $optionName));
        }
    }

    /**
     * @param InputInterface $input
     * @throws InputOptionException
     */
    private function checkAwsOptions(InputInterface $input): void
    {
        if (!$input->getOption('s3_bucket') && !$input->getOption('s3_object') && !$input->getOption('s3_region')
            && !$input->getOption('s3_access_key') && !$input->getOption('s3_secret')) {
            throw new InputOptionException('Neither local filename nor AWS object provided');
        }

        $this->checkAwsOption($input, 's3_bucket');
        $this->checkAwsOption($input, 's3_object');
        $this->checkAwsOption($input, 's3_region');
        $this->checkAwsOption($input, 's3_access_key');
        $this->checkAwsOption($input, 's3_secret');
    }

    /**
     * @param InputInterface $input
     * @return \Generator
     * @throws FileNotFoundException
     * @throws InputOptionException
     * @throws S3AccessException
     */
    private function iterateInputFileLines(InputInterface $input): \Generator
    {
        $filename = $input->getOption('filename');

        if ($filename !== null) {
            $fileHandle = fopen($filename, 'r');
            if (false === $fileHandle) {
                throw new FileNotFoundException($filename);
            }
            while (($line = fgetcsv($fileHandle, null, $input->getOption('delimiter'))) !== false) {
                yield $line;
            }
        } else {
            $this->checkAwsOptions($input);

            $s3Client = $this->s3ClientFactory->createClient($input->getOption('s3_region'),
                $input->getOption('s3_access_key'), $input->getOption('s3_secret'));

            try {
                $s3Response = $s3Client->getObject($input->getOption('s3_bucket'), $input->getOption('s3_object'));
            } catch (\Exception $e) {
                throw new S3AccessException();
            }
            foreach (explode(PHP_EOL, $s3Response) as $line) {
                yield str_getcsv($line, $input->getOption('delimiter'));
            }
        }
    }

    /**
     * Maps CSV line values to the command fields (getFields())
     *
     * @param array $line
     * @return array
     */
    protected function mapFileLineByFieldNames(array $line): array
    {
        $fieldNames = $this->getFields();
        $fieldValues = [];

        $numberOfLineElement = 0;
        foreach ($fieldNames as $fieldName) {
            $fieldValues[$fieldName] = array_key_exists($numberOfLineElement, $line) ? $line[$numberOfLineElement] : null;
            $numberOfLineElement++;
        }

        return $fieldValues;
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param Entity $entity
     * @throws ValidationException
     */
    protected function validateEntity(Entity $entity): void
    {
        $entity->validate();
    }

    /**
     * Checks if the record with same primary key already exists
     *
     * @param Entity $entity
     * @return bool
     */
    protected function checkIfExists(Entity $entity): bool
    {
        return $this->storage->read($entity->getTable(), [$entity->getKey()]) !== null;
    }

    /**
     * Entry point to the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $alreadyExistingRowsCount = $rowsAdded = 0;

        $lineNumber = 0;
        foreach ($this->iterateInputFileLines($input) as $line) {
            $lineNumber++;
            $entity = $this->buildEntity($this->mapFileLineByFieldNames($line));
            try {
                $this->validateEntity($entity);
            } catch (ValidationException $e) {
                $this->io->warning(sprintf('The process has been terminated because the line %d of the file is invalid:', $lineNumber));
                $this->io->error(sprintf('%s', $e->getMessage()));
                return;
            }

            if ($this->checkIfExists($entity)) {
                $alreadyExistingRowsCount++;
                if (!$this->updateMode) {
                    continue;
                }
            }

            $entityData = $entity->getData();
            $this->storage->insert($entity->getTable(), [$entity->getKey() => $entityData[$entity->getKey()]], $entityData);
            $rowsAdded++;
        }

        $messageOnUpdated = $this->updateMode ? 'updated' : 'were skipped as they already existed';
        $this->io->success(sprintf('Data has been ingested successfully. %d records created, %d records %s.', $rowsAdded, $alreadyExistingRowsCount, $messageOnUpdated));
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