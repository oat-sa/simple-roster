<?php

namespace App\Command;

use App\Entity\Entity;
use App\Storage\Storage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

    public function __construct(Storage $storage)
    {
        parent::__construct();
        $this->storage = $storage;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('filename', InputArgument::REQUIRED, 'The filename with CSV data')
            ->addArgument('delimiter', InputArgument::OPTIONAL, 'CSV delimiter used in file ("," or "; normally)', ',');
    }

    abstract protected function getFields(): array;

    abstract protected function buildEntity(array $fields): Entity;

    /**
     * @todo retrieving the file from Amazon S3
     *
     * @param InputInterface $input
     * @return \Generator
     */
    protected function getData(InputInterface $input): \Generator
    {
        $filename = $input->getArgument('filename');

        // maybe load file line by line?
        $fileHandle = fopen($filename, 'r');
        while (($line = fgetcsv($fileHandle, null, $input->getArgument('delimiter'))) !== false) {
            yield $line;
        }
    }

    protected function mapFileLineByFieldNames(array $line): array
    {
        $fieldNames = $this->getFields();
        $fieldValues = [];

        $numberOfLineElement = 0;
        foreach ($fieldNames as $fieldName) {
            $fieldValues[$fieldName] = $line[$numberOfLineElement];
            $numberOfLineElement++;
        }

        return $fieldValues;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        foreach ($this->getData($input) as $line) {
            $entity = $this->buildEntity($this->mapFileLineByFieldNames($line));
            $entity->validate();
            $entityData = $entity->getData();
            $this->storage->insert('users', $entityData, [$entity->getKey() => $entityData[$entity->getKey()]]);
        }

        $this->io->success(sprintf('hello'));
    }
}