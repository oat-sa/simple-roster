<?php declare(strict_types=1);

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Result\BulkResult;
use App\Command\CommandWatcherTrait;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BulkCreateUsersAssignmentsCommand extends Command
{
    use CommandWatcherTrait;

    public const NAME = 'roster:assignments:bulk-create';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    private $ingesterSourceRegistry;

    /** @var BulkCreateUsersAssignmentsService */
    private $bulkAssignmentsCreateService;

    /** @var BulkResult[] */
    private $failedBulkResults = [];

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkCreateUsersAssignmentsService $bulkCreateUsersAssignmentService
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->bulkAssignmentsCreateService = $bulkCreateUsersAssignmentService;

        parent::__construct(self::NAME);
    }

    protected function configure()
    {
        $this->setDescription(
            'Responsible for creating new user assignments based on user list (Local file, S3 bucket)'
        );

        $this->addArgument(
            'source',
            InputArgument::REQUIRED,
            sprintf(
                "Source type to ingest from, possible values: ['%s']",
                implode("', '", array_keys($this->ingesterSourceRegistry->all()))
            )
        );

        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Source path to ingest from'
        );

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

        $this->addOption(
            'batch',
            'b',
            InputOption::VALUE_REQUIRED,
            'Batch size',
            self::DEFAULT_BATCH_SIZE
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'To apply actual database modifications or not'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startWatch(self::NAME, __FUNCTION__);
        $consoleOutput = $this->ensureConsoleOutput($output);
        $style = new SymfonyStyle($input, $consoleOutput);
        $batchSize = (int)$input->getOption('batch');
        $isDryRun = !(bool)$input->getOption('force');

        if (!$this->promptUser($style, $isDryRun)) {
            $style->success('Aborting.');

            return 0;
        }

        $section = $consoleOutput->section();
        $section->writeln('Starting assignment creation...');

        try {
            $source = $this->getIngesterSource($input);
            $bulkOperationCollection = new BulkOperationCollection($isDryRun);

            $numberOfProcessedAssignments = 0;
            foreach ($source->getContent() as $row) {
                if (!isset($row['username'])) {
                    throw new RuntimeException("Column 'username' cannot be found in source CSV file.");
                }

                $operation = new BulkOperation($row['username'], BulkOperation::TYPE_CREATE);

                $bulkOperationCollection->add($operation);

                if (count($bulkOperationCollection) % $batchSize !== 0) {
                    continue;
                }

                $numberOfProcessedAssignments += count($bulkOperationCollection);
                $bulkResult = $this->processOperationCollection($bulkOperationCollection);

                if ($bulkResult->hasFailures()) {
                    $this->failedBulkResults[] = $bulkResult;
                }

                $section->overwrite(
                    sprintf(
                        'Processed: %s, batched errors: %s',
                        $numberOfProcessedAssignments,
                        count($this->failedBulkResults)
                    )
                );
            }

            // Process remaining operations
            if (!$bulkOperationCollection->isEmpty()) {
                $numberOfProcessedAssignments += count($bulkOperationCollection);

                $this->processRemainingOperations($bulkOperationCollection);

                $section->overwrite(
                    sprintf(
                        'Processed: %s, batched errors: %s',
                        $numberOfProcessedAssignments,
                        count($this->failedBulkResults)
                    )
                );
            }
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        }

        $style->newLine(2);
        $style->success(sprintf(
            "Successfully created '%s' assignments out of '%s'.",
            $numberOfProcessedAssignments - count($this->failedBulkResults) * $batchSize,
            $numberOfProcessedAssignments
        ));

        foreach ($this->failedBulkResults as $failedBulkResult) {
            $style->error(sprintf("Bulk operation error: '%s'", json_encode($failedBulkResult)));
        }

        $style->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));

        return 0;
    }

    private function processOperationCollection(BulkOperationCollection $bulkOperationCollection): BulkResult
    {
        $bulkResult = $this->bulkAssignmentsCreateService->process($bulkOperationCollection);

        $bulkOperationCollection->clear();

        return $bulkResult;
    }

    /**
     * @throws LogicException
     */
    private function ensureConsoleOutput(OutputInterface $output): ConsoleOutputInterface
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

    private function promptUser(SymfonyStyle $style, bool $isDryRun): bool
    {
        $style->title('Simple Roster - Bulk Assignment Creation');
        $style->text(
            'You are about to create new assignments for every provided user. ' .
            'For each new assignment the line item of the previous assignment will be used.'
        );

        if (!$isDryRun) {
            $style->note(
                'Dry mode is deactivated, therefore ALL database modifications will get applied.'
            );
        }

        return $style->askQuestion(new ConfirmationQuestion('Do you want to proceed?'));
    }

    private function getIngesterSource(InputInterface $input): IngesterSourceInterface
    {
        return $this->ingesterSourceRegistry
            ->get($input->getArgument('source'))
            ->setPath($input->getArgument('path'))
            ->setDelimiter($input->getOption('delimiter'))
            ->setCharset($input->getOption('charset'));
    }

    private function processRemainingOperations(BulkOperationCollection $bulkOperationCollection): BulkResult
    {
        $bulkResult = $this->processOperationCollection($bulkOperationCollection);

        if ($bulkResult->hasFailures()) {
            $this->failedBulkResults[] = $bulkResult;
        }

        return $bulkResult;
    }
}
