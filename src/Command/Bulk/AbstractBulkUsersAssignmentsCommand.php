<?php declare(strict_types=1);
/**
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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use App\Bulk\Result\BulkResult;
use App\Command\CommandWatcherTrait;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractBulkUsersAssignmentsCommand extends Command
{
    use CommandWatcherTrait;

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var string */
    private $name;

    /** @var IngesterSourceRegistry */
    private $ingesterSourceRegistry;

    /** @var BulkOperationCollectionProcessorInterface */
    private $bulkOperationCollectionProcessor;

    /** @var BulkResult[] */
    protected $failedBulkResults = [];

    public function __construct(
        string $name,
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkOperationCollectionProcessorInterface $bulkOperationCollectionProcessor
    ) {
        $this->name = $name;
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->bulkOperationCollectionProcessor = $bulkOperationCollectionProcessor;

        parent::__construct($name);
    }

    abstract protected function process(
        InputInterface $input,
        ConsoleOutputInterface $consoleOutput,
        int $batchSize,
        bool $isDryRun
    ): int;

    protected function configure()
    {
        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            sprintf(
                "Source type to ingest from, possible values: ['%s']",
                implode("', '", array_keys($this->ingesterSourceRegistry->all()))
            )
        );

        $this->addArgument('path', InputArgument::REQUIRED, 'Source path to ingest from');

        $this->addOption(
            'delimiter',
            'd',
            InputOption::VALUE_REQUIRED,
            'CSV delimiter',
            IngesterSourceInterface::DEFAULT_CSV_DELIMITER
        );

        $this->addOption(
            'charset',
            'c',
            InputOption::VALUE_REQUIRED,
            'CSV source charset',
            IngesterSourceInterface::DEFAULT_CSV_CHARSET
        );

        $this->addOption('batch', 'b', InputOption::VALUE_REQUIRED, 'Batch size', self::DEFAULT_BATCH_SIZE);

        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'To apply actual database modifications or not');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startWatch($this->name, __FUNCTION__);
        $consoleOutput = $this->ensureConsoleOutput($output);

        $style = new SymfonyStyle($input, $output);
        $batchSize = (int)$input->getOption('batch');
        $isDryRun = !(bool)$input->getOption('force');

        $result = $this->process($input, $consoleOutput, $batchSize, $isDryRun);

        $style->note(sprintf('Took: %s', $this->stopWatch($this->name)));

        return $result;
    }

    protected function processOperationCollection(BulkOperationCollection $bulkOperationCollection): BulkResult
    {
        $bulkResult = $this->bulkOperationCollectionProcessor->process($bulkOperationCollection);

        if ($bulkResult->hasFailures()) {
            $this->failedBulkResults[] = $bulkResult;
        }

        $bulkOperationCollection->clear();

        return $bulkResult;
    }

    /**
     * @throws LogicException
     */
    protected function ensureConsoleOutput(OutputInterface $output): ConsoleOutputInterface
    {
        if (!$output instanceof ConsoleOutputInterface) {
            throw new LogicException(
                sprintf(
                    "Output must be instance of '%s' because of section usage.",
                    ConsoleOutputInterface::class
                )
            );
        }

        return $output;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validateRow(array $row)
    {
        if (!isset($row['username'])) {
            throw new InvalidArgumentException("Column 'username' cannot be found in source CSV file.");
        }
    }

    protected function getIngesterSource(InputInterface $input): IngesterSourceInterface
    {
        return $this->ingesterSourceRegistry
            ->get($input->getArgument('source'))
            ->setPath($input->getArgument('path'))
            ->setDelimiter($input->getOption('delimiter'))
            ->setCharset($input->getOption('charset'));
    }

    protected function displayResult(SymfonyStyle $style, int $numberOfProcessedAssignments, int $batchSize): void
    {
        $style->newLine(2);
        $style->success(sprintf(
            "Successfully processed '%s' assignments out of '%s'.",
            max(0, $numberOfProcessedAssignments - count($this->failedBulkResults) * $batchSize),
            $numberOfProcessedAssignments
        ));

        foreach ($this->failedBulkResults as $failedBulkResult) {
            $style->error(sprintf("Bulk operation error: '%s'", json_encode($failedBulkResult)));
        }
    }

    protected function overwriteSection(ConsoleSectionOutput $section, int $numberOfProcessedAssignments): void
    {
        $section->overwrite(
            sprintf(
                'Processed: %s, batched errors: %s',
                $numberOfProcessedAssignments,
                count($this->failedBulkResults)
            )
        );
    }
}
