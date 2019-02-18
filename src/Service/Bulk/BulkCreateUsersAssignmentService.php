<?php declare(strict_types=1);

namespace App\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use App\Bulk\Result\BulkResult;
use App\Entity\Assignment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class BulkCreateUsersAssignmentService implements BulkOperationCollectionProcessorInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $logBuffer = [];

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function process(BulkOperationCollection $operationCollection): BulkResult
    {
        $result = new BulkResult();

        $this->entityManager->beginTransaction();

        foreach ($operationCollection as $operation) {
            if ($operation->getType() === BulkOperation::TYPE_CREATE) {
                try {
                    /** @var User $user */
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->getByUsernameWithAssignments($operation->getIdentifier());

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

                    $this->logBuffer[] = [
                        'message' => sprintf(
                            'Successful assignment create operation (id=`%s`) for user with username=`%s`.',
                            $newAssignment->getId(),
                            $user->getUsername()
                        ),
                        'context' => $operation->getContext(),
                    ];
                } catch (Throwable $exception) {
                    $result->addBulkOperationFailure($operation);
                }
            } else {
                $result->addBulkOperationFailure($operation);
            }
        }

        if (!$result->hasFailures()) {
            $this->entityManager->commit();

            foreach ($this->logBuffer as $logRecord) {
                $this->logger->info($logRecord['message'], $logRecord['context']);
            }
        } else {
            $this->entityManager->rollback();
        }

        return $result;
    }
}
