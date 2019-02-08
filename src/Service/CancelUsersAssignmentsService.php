<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CancelUsersAssignmentsService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function cancel(User ...$users): array
    {
        $result = [];
        $this->entityManager->beginTransaction();
        foreach ($users as $user) {
            foreach ($user->getAvailableAssignments() as $assignment) {
                $assignment->setState(Assignment::STATE_CANCELLED);
            }
            $result[$user->getUsername()] = true;
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        return $result;
    }
}
