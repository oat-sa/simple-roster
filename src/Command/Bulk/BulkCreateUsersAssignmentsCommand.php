<?php declare(strict_types=1);

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Command\CommandWatcherTrait;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BulkCreateUsersAssignmentsCommand extends AbstractBulkUsersAssignmentsCommand
{
    use CommandWatcherTrait;

    public const NAME = 'roster:assignments:bulk-create';

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkCreateUsersAssignmentsService $bulkCreateUsersAssignmentService
    ) {
        parent::__construct(self::NAME, $ingesterSourceRegistry, $bulkCreateUsersAssignmentService);
    }

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Responsible for creating user assignments based on user list (Local file, S3 bucket)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->startWatch(self::NAME, __FUNCTION__);
        $consoleOutput = $this->ensureConsoleOutput($output);
        $style = new SymfonyStyle($input, $consoleOutput);
        $batchSize = (int)$input->getOption('batch');
        $isDryRun = !(bool)$input->getOption('force');

        $style->title('Simple Roster - Bulk Assignment Creation');

        $section = $consoleOutput->section();
        $section->writeln('Starting assignment creation...');

        try {
            $source = $this->getIngesterSource($input);
            $bulkOperationCollection = new BulkOperationCollection();

            $numberOfProcessedAssignments = 0;
            foreach ($source->getContent() as $row) {
                $this->validateRow($row);

                $operation = new BulkOperation($row['username'], BulkOperation::TYPE_CREATE);

                $operation->setIsDryRun($isDryRun);

                $bulkOperationCollection->add($operation);

                if (count($bulkOperationCollection) % $batchSize !== 0) {
                    continue;
                }

                $numberOfProcessedAssignments += count($bulkOperationCollection);
                $this->processOperationCollection($bulkOperationCollection);

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
                $this->processOperationCollection($bulkOperationCollection);

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

        $this->displayResult($style, $numberOfProcessedAssignments, $batchSize);
        $style->note(sprintf('Took: %s', $this->stopWatch(self::NAME)));

        return 0;
    }
}
