<?php declare(strict_types=1);

namespace App\Service\Bulk;

use App\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use App\Entity\Assignment;
use App\Entity\User;
use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Result\BulkResult;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

class BulkCreateUsersAssignmentService implements BulkOperationCollectionProcessorInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function process(BulkOperationCollection $operationCollection): BulkResult
    {
        $result = new BulkResult();

        $this->entityManager->beginTransaction();

        foreach ($operationCollection as $operation) {
            if ($operation->getType() === BulkOperation::TYPE_CREATE) {
                try {
                    /** @var UserRepository $userRepository */
                    $userRepository = $this->entityManager->getRepository(User::class);
                    $user = $userRepository->getByUsernameWithAssignments($operation->getIdentifier());

                    $lastAssignment = $user->getLastAssignment();

                    foreach ($user->getAvailableAssignments() as $assignment) {
                        $assignment->setState(Assignment::STATE_CANCELLED);
                    }

                    $newAssignment = (new Assignment())
                        ->setState(Assignment::STATE_READY)
                        ->setLineItem($lastAssignment->getLineItem());

                    $user->addAssignment($newAssignment);

                    $this->entityManager->persist($newAssignment);
                    $this->entityManager->flush();

                    $result->addBulkOperationSuccess($operation);
                } catch (Throwable $exception) {
                    $result->addBulkOperationFailure($operation);
                }
            } else {
                $result->addBulkOperationFailure($operation);
            }
        }

        if (!$result->hasFailures()) {
            $this->entityManager->commit();
        } else {
            $this->entityManager->rollback();
        }

        return $result;
    }
}
