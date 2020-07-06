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

namespace App\Command\GarbageCollector;

use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use Carbon\Carbon;
use DateInterval;
use Exception;
use InvalidArgumentException;
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

    private const DEFAULT_BATCH_SIZE = 1000;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var DateInterval */
    private $cleanUpInterval;

    /**
     * @throws Exception
     */
    public function __construct(
        AssignmentRepository $assignmentRepository,
        LoggerInterface $logger,
        string $cleanUpInterval
    ) {
        parent::__construct(self::NAME);

        $this->assignmentRepository = $assignmentRepository;
        $this->logger = $logger;
        $this->cleanUpInterval = new DateInterval($cleanUpInterval);
    }

    protected function configure()
    {
        parent::configure();

        $this->setDescription(
            sprintf(
                "Transitions assignments stuck in '%s' state for a given amount of time to '%s' state",
                Assignment::STATE_STARTED,
                Assignment::STATE_COMPLETED
            )
        );

        $this->addOption(
            'batch-size',
            'b',
            InputOption::VALUE_REQUIRED,
            'Number of assignments to process per batch',
            self::DEFAULT_BATCH_SIZE
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
            $batchSize = (int)$input->getOption('batch-size');
            if ($batchSize < 1) {
                throw new InvalidArgumentException("Invalid 'batch-size' argument received.");
            }

            $isDryRun = !(bool)$input->getOption('force');
            $numberOfCollectedAssignments = $this->collectStuckAssignments($batchSize, $isDryRun);

            $successMessage = $numberOfCollectedAssignments !== 0
                ? sprintf(
                    "Total of '%s' stuck assignments were successfully collected.",
                    $numberOfCollectedAssignments
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
            $stuckAssignments = $this->assignmentRepository->findAllByStateAndUpdatedAtPaginated(
                Assignment::STATE_STARTED,
                Carbon::now()->subtract($this->cleanUpInterval)->toDateTime(),
                0,
                $batchSize
            );

            /** @var Assignment $assignment */
            $assignmentCount = $stuckAssignments->getIterator()->count();
            foreach ($stuckAssignments as $assignment) {
                $assignment->complete();

                if (!$isDryRun) {
                    $this->assignmentRepository->persist($assignment);
                }

                $numberOfCollectedAssignments++;
                $this->logger->info(
                    sprintf(
                        "Assignment with id='%s' of user with username='%s' has been collected and marked as '%s' by garbage collector.",
                        $assignment->getId(),
                        $assignment->getUser()->getUsername(),
                        $assignment->getState()
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
