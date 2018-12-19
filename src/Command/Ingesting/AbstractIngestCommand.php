<?php

namespace App\Command\Ingesting;

use App\Command\Ingesting\Exception\FileNotFoundException;
use App\Entity\Entity;
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
     * Whether to skip those records already existing or update with new values
     *
     * @var bool
     */
    protected $updateMode = false;

    public function __construct(Storage $storage)
    {
        parent::__construct();
        $this->storage = $storage;
    }

    /**
     * Sets command name, input and metadata
     */
    protected function configure(): void
    {
        $this
            ->addOption('filename', null, InputOption::VALUE_OPTIONAL, 'The filename with CSV data')
            ->addOption('delimiter', null, InputOption::VALUE_OPTIONAL, 'CSV delimiter used in file ("," or "; normally)', ',');
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
     * @todo retrieving the file from Amazon S3
     *
     * @param InputInterface $input
     * @return \Generator
     * @throws FileNotFoundException
     */
    protected function getData(InputInterface $input): \Generator
    {
        $filename = $input->getOption('filename');

        $fileHandle = fopen($filename, 'r');
        if (false === $fileHandle) {
            throw new FileNotFoundException($filename);
        }
        while (($line = fgetcsv($fileHandle, null, $input->getOption('delimiter'))) !== false) {
            yield $line;
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
     * @throws \Exception
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
        foreach ($this->getData($input) as $line) {
            $lineNumber++;
            $entity = $this->buildEntity($this->mapFileLineByFieldNames($line));
            try {
                $this->validateEntity($entity);
            } catch (\Exception $e) {
                $this->io->warning(sprintf('The process has been terminated because of an error on line %d of file:', $lineNumber));
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