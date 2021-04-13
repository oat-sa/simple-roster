<?php

/*
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Command\Ingester;

use InvalidArgumentException;
use League\Csv\Reader;
use OAT\SimpleRoster\Command\BlackfireProfilerTrait;
use OAT\SimpleRoster\Command\CommandProgressBarFormatterTrait;
use OAT\SimpleRoster\Csv\CsvReaderBuilder;
use OAT\SimpleRoster\Storage\StorageRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

abstract class AbstractCsvIngesterCommand extends Command
{
    use CommandProgressBarFormatterTrait;
    use BlackfireProfilerTrait;

    private const DEFAULT_BATCH_SIZE = '1000';

    /** @var CsvReaderBuilder */
    protected $csvReaderBuilder;

    /** @var StorageRegistry */
    protected $storageRegistry;

    /** @var Reader */
    protected $csvReader;

    /** @var SymfonyStyle */
    protected $symfonyStyle;

    /** @var bool */
    protected $isDryRun;

    /** @var int */
    protected $batchSize;

    /** @var ProgressBar */
    protected $progressBar;

    public function __construct(CsvReaderBuilder $csvReaderBuilder, StorageRegistry $storageRegistry)
    {
        $this->csvReaderBuilder = $csvReaderBuilder;
        $this->storageRegistry = $storageRegistry;

        parent::__construct($this->getIngesterCommandName());
    }

    abstract protected function getIngesterCommandName(): string;

    protected function configure(): void
    {
        $this->addBlackfireProfilingOption();

        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Relative filepath'
        );

        $this->addOption(
            'storage',
            's',
            InputOption::VALUE_REQUIRED,
            sprintf(
                'Filesystem storage identifier. Available storages: <options=bold>%s</>',
                implode(', ', $this->storageRegistry->getAllStorageIds())
            ),
            StorageRegistry::DEFAULT_STORAGE
        );

        $this->addOption(
            'delimiter',
            'd',
            InputOption::VALUE_REQUIRED,
            'CSV column delimiter',
            CsvReaderBuilder::DEFAULT_CSV_DELIMITER
        );

        $this->addOption(
            'batch',
            'b',
            InputOption::VALUE_REQUIRED,
            'Batch size',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'To apply changes on database');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);

        $this->isDryRun = !(bool)$input->getOption('force');
        $this->batchSize = (int)$input->getOption('batch');
        $this->progressBar = $this->createFormattedProgressBar($output);

        $this->csvReader = $this->csvReaderBuilder
            ->setDelimiter((string)$input->getOption('delimiter'))
            ->build(
                (string)$input->getArgument('path'),
                (string)$input->getOption('storage')
            );

        $process = new Process(['wc', '-l', $this->csvReader->getPathname()]);
        $process->run();

        // In tests with in memory adapter file content cannot be counted with process.
        $maxSteps = $process->getOutput() === ''
            ? $this->csvReader->count()
            : (int)$process->getOutput() - 1;

        $this->progressBar->setMaxSteps($maxSteps);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateRow(array $row, string ...$columns): void
    {
        foreach ($columns as $column) {
            if (!isset($row[$column])) {
                throw new InvalidArgumentException(sprintf("Column '%s' is not set in source file.", $column));
            }
        }
    }

    protected function batchProcessable(int $numberOfProcessedRows): bool
    {
        return $numberOfProcessedRows % $this->batchSize === 0;
    }
}
