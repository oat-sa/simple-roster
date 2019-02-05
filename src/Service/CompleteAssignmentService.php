<?php

namespace App\Service;

use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CompleteAssignmentService
{
    /** @var AssignmentRepository */
    private $assignmentRepository;

    public function __construct(AssignmentRepository $assignmentRepository)
    {
        $this->assignmentRepository = $assignmentRepository;
    }

    public function markAssignmentAsCompleted(int $assignmentId): void
    {
        $assignment = $this->assignmentRepository->find($assignmentId);

        if (!$assignment) {
            new NotFoundHttpException(sprintf('Assignment with id `%s` not found.', $assignmentId));
        }

        $assignment->setState(Assignment::STATE_COMPLETED);

        $this->assignmentRepository->persist($assignment);
        $this->assignmentRepository->flush();
    }
}
