<?php declare(strict_types=1);

namespace App\Command;

use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use Carbon\Carbon;
use DateInterval;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class AssignmentGarbageCollectorCommand extends Command
{
    public const NAME = 'roster:garbage-collector:assignment';

    private const BATCH_SIZE = 1000;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var DateInterval */
    private $cleanUpInterval;

    public function __construct(
        AssignmentRepository $assignmentRepository,
        LoggerInterface $logger,
        string $cleanUpInterval
    ) {
        parent::__construct(self::NAME);

        $this->assignmentRepository = $assignmentRepository;
        $this->logger = $logger;
        $this->cleanUpInterval = $cleanUpInterval;
    }

    protected function configure()
    {
        parent::configure();

        $this->setDescription(
            sprintf(
                'Transitions assignments stuck in `%s` state for a given amount of time to `%s` state',
                Assignment::STATE_STARTED,
                Assignment::STATE_COMPLETED
            )
        );

        $this->addOption(
            'batch-size',
            'b',
            InputOption::VALUE_REQUIRED,
            'Number of assignments to process per batch',
            self::BATCH_SIZE
        );

        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'To involve actual database modifications or not'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        try {
            $batchSize = $input->getOption('batch-size') ?? self::BATCH_SIZE;
            $isDryRun = !(bool)$input->getOption('force');
            $numberOfCollectedAssignments = $this->collectStuckAssignments($batchSize, $isDryRun);

            $successMessage = $numberOfCollectedAssignments !== 0
                ? sprintf(
                    'Total of `%s` stuck assignments were successfully marked as `%s`.',
                    $numberOfCollectedAssignments,
                    Assignment::STATE_COMPLETED
                )
                : 'Nothing to update.';

            $style->success($successMessage);

            return 0;
        } catch (Throwable $exception) {
            $style->error($exception->getMessage());

            return 1;
        }
    }

    /**
     * @throws Exception
     */
    private function collectStuckAssignments(int $batchSize, bool $isDryRun): int
    {
        $numberOfCollectedAssignments = 0;
        do {
            $stuckAssignments = $this->assignmentRepository->findAllByStateAndUpdatedAtPaged(
                Assignment::STATE_STARTED,
                Carbon::now()->subtract(new DateInterval($this->cleanUpInterval))->toDateTime(),
                0,
                $batchSize
            );


            /** @var Assignment $assignment */
            $assignmentCount = $stuckAssignments->getIterator()->count();
            foreach ($stuckAssignments as $assignment) {
                $assignment->setState(Assignment::STATE_COMPLETED);
                if (!$isDryRun) {
                    $this->assignmentRepository->persist($assignment);
                }

                $numberOfCollectedAssignments++;
                $this->logger->info(
                    sprintf(
                        'Assignment with id=`%s` has been marked as completed by garbage collector',
                        $assignment->getId()
                    )
                );
            }

            if (!$isDryRun) {
                $this->assignmentRepository->flush();
            }
        } while ($assignmentCount === $batchSize);

        return $numberOfCollectedAssignments;
    }
}
