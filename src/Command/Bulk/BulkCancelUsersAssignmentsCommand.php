<?php declare(strict_types=1);

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Entity\Assignment;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Service\Bulk\BulkUpdateUsersAssignmentsStateService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BulkCancelUsersAssignmentsCommand extends AbstractBulkUsersAssignmentsCommand
{
    public const NAME = 'roster:assignments:bulk-cancel';

    public function __construct(
        IngesterSourceRegistry $ingesterSourceRegistry,
        BulkUpdateUsersAssignmentsStateService $bulkAssignmentsUpdateService
    ) {
        parent::__construct(self::NAME, $ingesterSourceRegistry, $bulkAssignmentsUpdateService);
    }

    protected function configure()
    {
        parent::configure();

        $this->setDescription('Responsible for cancelling user assignments based on user list (Local file, S3 bucket)');
    }

    protected function process(InputInterface $input, ConsoleOutputInterface $consoleOutput, int $batchSize, bool $isDryRun): int
    {
        $style = new SymfonyStyle($input, $consoleOutput);
        $style->title('Simple Roster - Bulk Assignment Cancellation');

        $section = $consoleOutput->section();
        $section->writeln('Starting assignment cancellation...');

        try {
            $source = $this->getIngesterSource($input);
            $bulkOperationCollection = new BulkOperationCollection();

            $numberOfProcessedAssignments = 0;
            foreach ($source->getContent() as $row) {
                $this->validateRow($row);

                $operation = new BulkOperation(
                    $row['username'],
                    BulkOperation::TYPE_UPDATE,
                    ['state' => Assignment::STATE_CANCELLED]
                );

                $operation->setIsDryRun($isDryRun);

                $bulkOperationCollection->add($operation);

                if (count($bulkOperationCollection) % $batchSize !== 0) {
                    continue;
                }

                $numberOfProcessedAssignments += count($bulkOperationCollection);
                $this->processOperationCollection($bulkOperationCollection);

                $this->overwriteSection($section, $numberOfProcessedAssignments);
            }

            // Process remaining operations
            if (!$bulkOperationCollection->isEmpty()) {
                $numberOfProcessedAssignments += count($bulkOperationCollection);

                $this->processOperationCollection($bulkOperationCollection);

                $this->overwriteSection($section, $numberOfProcessedAssignments);
            }
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        }

        $this->displayResult($style, $numberOfProcessedAssignments, $batchSize);

        return 0;
    }
}
