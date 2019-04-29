<?php declare(strict_types=1);

namespace App\Command\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Ingester\Registry\IngesterSourceRegistry;
use App\Service\Bulk\BulkCreateUsersAssignmentsService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class BulkCreateUsersAssignmentsCommand extends AbstractBulkUsersAssignmentsCommand
{
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

    protected function process(InputInterface $input, ConsoleOutputInterface $consoleConsoleOutput, int $batchSize, bool $isDryRun): int
    {
        $style = new SymfonyStyle($input, $consoleConsoleOutput);
        $style->title('Simple Roster - Bulk Assignment Creation');

        $section = $consoleConsoleOutput->section();
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
