<?php declare(strict_types=1);

namespace App\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use App\Bulk\Result\BulkResult;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

class BulkUpdateUsersAssignmentsStateService implements BulkOperationCollectionProcessorInterface
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

            if ($operation->getType() == BulkOperation::TYPE_UPDATE) {
                try {
                    /** @var User $user */
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->getByUsernameWithAssignments($operation->getIdentifier());

                    foreach ($user->getAvailableAssignments() as $assignment) {
                        $assignment->setState($operation->getAttribute('state'));
                    }

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
