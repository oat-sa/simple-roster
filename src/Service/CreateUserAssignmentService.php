<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;

class CreateUserAssignmentService
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @throws EntityNotFoundException
     * @throws ORMException
     */
    public function create(User $user): Assignment
    {
        $lastAssignment = $user->getLastAssignment();
        if (null === $lastAssignment) {
            throw new EntityNotFoundException(
                sprintf(
                    "Assignment cannot be created for user '%s'. No previous assignments were found in database.",
                    $user->getUsername()
                )
            );
        }

        $this->cancelAllPreviousAssignments($user);

        $newAssignment = (new Assignment())
            ->setState(Assignment::STATE_READY)
            ->setLineItem($lastAssignment->getLineItem());

        $user->addAssignment($newAssignment);

        $this->userRepository->persist($user);

        return $newAssignment;
    }

    private function cancelAllPreviousAssignments(User $user): void
    {
        foreach ($user->getAvailableAssignments() as $assignment) {
            $assignment->setState(Assignment::STATE_CANCELLED);
        }
    }
}
