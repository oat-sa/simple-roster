<?php declare(strict_types=1);

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Result\BulkResult;
use App\Bulk\Result\BulkResultCollection;
use App\Command\CommandWatcherTrait;
use App\Entity\Assignment;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Ingester\Source\IngesterSourceInterface;
use App\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use LogicException;
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

class BulkCancelUsersAssignmentsCommand extends Command
{
    // TODO add watcher
    use CommandWatcherTrait;

    public const NAME = 'roster:assignments:bulk-cancel';

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var IngesterSourceRegistry */
    private $ingesterSourceRegistry;

    /** @var BulkUpdateUsersAssignmentsStateService */
    private $bulkAssignmentsUpdateService;

    /** @var int */
    private $numberOfErrors = 0;

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkUpdateUsersAssignmentsStateService $bulkAssignmentsUpdateService
    ) {
        $this->ingesterSourceRegistry = $ingesterSourceRegistry;
        $this->bulkAssignmentsUpdateService = $bulkAssignmentsUpdateService;

        parent::__construct(self::NAME);
    }

    protected function configure()
    {
        $this->setDescription(
            'Responsible for cancelling user assignments based on user list (Local file, S3 bucket)'
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
        $batchSize = (int)$input->getOption('batch') ?: self::DEFAULT_BATCH_SIZE;
        $isDryRun = !(bool)$input->getOption('force');

        if (!$this->promptUser($style, $isDryRun)) {
            $style->success('Aborting.');

            return 0;
        }

        $section = $consoleOutput->section();
        $section->writeln('Starting assignment cancellation...');

        try {
            $source = $this->getIngesterSource($input);
            $bulkOperationCollection = new BulkOperationCollection($isDryRun);
            $bulkResultCollection = new BulkResultCollection();

            $totalNumberOfAssignments = 0;
            foreach ($source->getContent() as $row) {
                if (!isset($row['username'])) {
                    continue;
                }

                $this->processRow($row['username'], $bulkOperationCollection, $bulkResultCollection, $batchSize);
                $totalNumberOfAssignments++;

                $this->refreshSection($section, $totalNumberOfAssignments, $batchSize);
            }

            if (!$bulkOperationCollection->isEmpty()) {
                $bulkResult = $this->bulkAssignmentsUpdateService->process($bulkOperationCollection);
                $bulkResultCollection->add($bulkResult);
            }
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        }

        $numberOfCancelledAssignments = 0;
        $totalNumberOfAssignments = 0;
        /** @var BulkResult $bulkResult */
        foreach ($bulkResultCollection as $bulkResult) {
            $totalNumberOfAssignments += $bulkResult->count();
            if ($bulkResult->hasFailures()) {
                continue;
            }

            $numberOfCancelledAssignments += $bulkResult->count();
        }

        $style->newLine(2);
        $style->success(sprintf(
            "Successfully cancelled '%s' assignments out of '%s'.",
            $numberOfCancelledAssignments,
            $totalNumberOfAssignments
        ));

        $style->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));

        return 0;
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
        $style->title('Simple Roster - Bulk Assignment Cancellation');
        $style->text(sprintf(
            "You are about to update all assignments with any of '%s' states to '%s' state for every provided user.",
            strtoupper(implode(', ', [Assignment::STATE_READY, Assignment::STATE_STARTED])),
            strtoupper(Assignment::STATE_CANCELLED)
        ));

        if (!$isDryRun) {
            $style->note(
                'Dry mode is deactivated, therefore ALL database modifications will get applied.'
            );
        }

        return $style->askQuestion(new ConfirmationQuestion('Do you want to proceed?'));
    }

    private function processRow(
        string $username,
        BulkOperationCollection $bulkOperationCollection,
        BulkResultCollection $bulkResultCollection,
        int $batchSize
    ): void {
        $operation = new BulkOperation($username, BulkOperation::TYPE_UPDATE);
        $bulkOperationCollection->add($operation);
        if (count($bulkOperationCollection) % $batchSize === 0) {
            $bulkResult = $this->bulkAssignmentsUpdateService->process($bulkOperationCollection);

            if ($bulkResult->hasFailures()) {
                $this->numberOfErrors++;
            }

            $bulkResultCollection->add($bulkResult);
            $bulkOperationCollection->clear();
        }
    }

    private function getIngesterSource(InputInterface $input): IngesterSourceInterface
    {
        return $this->ingesterSourceRegistry
            ->get($input->getArgument('source'))
            ->setPath($input->getArgument('path'))
            ->setDelimiter($input->getOption('delimiter'))
            ->setCharset($input->getOption('charset'));
    }

    private function refreshSection(ConsoleSectionOutput $section, int $numberOfAssignments, int $batchSize): void
    {
        if ($numberOfAssignments % $batchSize === 0) {
            $section->overwrite(
                sprintf('Success: %s, batched errors: %s', $numberOfAssignments, $this->numberOfErrors)
            );
        }
    }
}
