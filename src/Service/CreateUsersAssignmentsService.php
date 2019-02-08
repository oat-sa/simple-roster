<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CreateUsersAssignmentsService
{
    /** @var CreateUserAssignmentService */
    private $createUserAssignmentService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        CreateUserAssignmentService $createUserAssignmentService,
        EntityManagerInterface $entityManager
    ) {
        $this->createUserAssignmentService = $createUserAssignmentService;
        $this->entityManager = $entityManager;
    }

    public function create(User ...$users): array
    {
        $result = [];
        $isSuccessfulTransaction = true;

        $this->entityManager->beginTransaction();
        foreach ($users as $user) {
            try {
                $this->createUserAssignmentService->create($user);
                $result[$user->getUsername()] = true;
            } catch (Exception $exception) {
                $isSuccessfulTransaction = false;
                $result[$user->getUsername()] = false;
                continue;
            }
        }

        if ($isSuccessfulTransaction) {
            $this->entityManager->flush();
            $this->entityManager->commit();
        } else {
            $this->entityManager->rollback();
        }

        return $result;
    }
}
