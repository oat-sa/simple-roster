<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Assignment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CancelUsersAssignmentsService
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function cancel(User ...$users): void
    {
        try {
            $this->entityManager->beginTransaction();
            foreach ($users as $user) {
                foreach ($user->getAvailableAssignments() as $assignment) {
                    $assignment->setState(Assignment::STATE_CANCELLED);
                }
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
