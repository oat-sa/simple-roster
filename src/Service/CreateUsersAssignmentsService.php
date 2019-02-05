<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Generator;

class CreateUsersAssignmentsService
{
    /** @var CreateUserAssignmentService */
    private $createUserAssignmentService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(CreateUserAssignmentService $createUserAssignmentService, EntityManagerInterface $entityManager)
    {
        $this->createUserAssignmentService = $createUserAssignmentService;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Exception
     */
    public function create(User ...$users): Generator
    {
        try {
            $this->entityManager->beginTransaction();
            foreach ($users as $user) {
                yield $this->createUserAssignmentService->create($user);
            }
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
