<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Exception\AssignmentNotFoundException;
use App\Repository\AssignmentRepository;
use Psr\Log\LoggerInterface;

class CompleteUserAssignmentService
{
    /** @var AssignmentRepository */
    private $assignmentRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(AssignmentRepository $assignmentRepository, LoggerInterface $logger)
    {
        $this->assignmentRepository = $assignmentRepository;
        $this->logger = $logger;
    }

    /**
     * @throws AssignmentNotFoundException
     */
    public function markAssignmentAsCompleted(int $assignmentId): void
    {
        $assignment = $this->assignmentRepository->find($assignmentId);

        if (!$assignment) {
            throw new AssignmentNotFoundException(sprintf('Assignment with id `%s` not found.', $assignmentId));
        }

        $assignment->setState(Assignment::STATE_COMPLETED);

        $this->assignmentRepository->persist($assignment);
        $this->assignmentRepository->flush();

        $this->logger->info(
            sprintf(
                'Assignment with id=`%s` of user with username=`%s` has been marked as completed.',
                $assignmentId,
                $assignment->getUser()->getUsername()
            ),
            ['lineItem' => $assignment->getLineItem()]
        );
    }
}
