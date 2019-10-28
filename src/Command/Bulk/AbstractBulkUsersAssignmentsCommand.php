<?php

declare(strict_types=1);

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
use App\Command\CommandProgressBarFormatterTrait;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractBulkUsersAssignmentsCommand extends Command
{
    use CommandProgressBarFormatterTrait;

    protected const ARGUMENT_SOURCE = 'source';
    protected const ARGUMENT_PATH = 'path';

    protected const OPTION_DELIMITER = 'delimiter';
    protected const OPTION_CHARSET = 'charset';
    protected const OPTION_BATCH = 'batch'; // TODO batch-size
    protected const OPTION_FORCE = 'force';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    protected $ingesterSourceRegistry;

    /** @var BulkOperationCollectionProcessorInterface */
    private $bulkOperationCollectionProcessor;

    /** @var BulkResult[] */
    protected $failedBulkResults = [];

    /** @var SymfonyStyle */
    protected $symfonyStyle;

    /** @var int */
    protected $batchSize;

    /** @var bool */
    protected $isDryRun;

    /** @var IngesterSourceInterface */
    protected $ingesterSource;

    public function __construct(
        string $name,
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkOperationCollectionProcessorInterface $bulkOperationCollectionProcessor
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->bulkOperationCollectionProcessor = $bulkOperationCollectionProcessor;

        parent::__construct($name);
    }

    abstract protected function process(OutputInterface $output): int;

    protected function configure()
    {
        $this->addArgument(
            self::ARGUMENT_SOURCE,
            InputArgument::REQUIRED,
            sprintf(
                "Source type to ingest from, possible values: ['%s']",
                implode("', '", array_keys($this->ingesterSourceRegistry->all()))
            )
        );

        $this->addArgument(self::ARGUMENT_PATH, InputArgument::REQUIRED, 'Source path to ingest from');

        $this->addOption(
            self::OPTION_DELIMITER,
            'd',
            InputOption::VALUE_REQUIRED,
            'CSV delimiter',
            IngesterSourceInterface::DEFAULT_CSV_DELIMITER
        );

        $this->addOption(
            self::OPTION_CHARSET,
            'c',
            InputOption::VALUE_REQUIRED,
            'CSV source charset',
            IngesterSourceInterface::DEFAULT_CSV_CHARSET
        );

        $this->addOption(self::OPTION_BATCH, 'b', InputOption::VALUE_REQUIRED, 'Batch size', self::DEFAULT_BATCH_SIZE);

        $this->addOption(
            self::OPTION_FORCE,
            'f',
            InputOption::VALUE_NONE,
            'To apply actual database modifications or not'
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);

        $this->batchSize = (int)$input->getOption(self::OPTION_BATCH);
        $this->isDryRun = !(bool)$input->getOption(self::OPTION_FORCE);

        $this->ingesterSource = $this->ingesterSourceRegistry
            ->get((string)$input->getArgument(self::ARGUMENT_SOURCE))
            ->setPath((string)$input->getArgument(self::ARGUMENT_PATH))
            ->setDelimiter((string)$input->getOption(self::OPTION_DELIMITER))
            ->setCharset((string)$input->getOption(self::OPTION_CHARSET));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->process($output);
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
     * @throws InvalidArgumentException
     */
    protected function validateRow(array $row): void
    {
        if (!isset($row['username'])) {
            throw new InvalidArgumentException("Column 'username' cannot be found in source CSV file.");
        }
    }

    protected function displayResult(int $numberOfProcessedAssignments): void
    {
        $this->symfonyStyle->newLine(2);
        $this->symfonyStyle->success(sprintf(
            "Successfully processed '%s' assignments out of '%s'.",
            max(0, $numberOfProcessedAssignments - count($this->failedBulkResults) * $this->batchSize),
            $numberOfProcessedAssignments
        ));

        foreach ($this->failedBulkResults as $failedBulkResult) {
            $this->symfonyStyle->error(sprintf("Bulk operation error: '%s'", json_encode($failedBulkResult)));
        }
    }
}
