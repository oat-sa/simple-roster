<?php declare(strict_types=1);

namespace App\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use App\Bulk\Result\BulkResult;
use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
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
            if ($operation->getType() !== BulkOperation::TYPE_CREATE) {
                $this->logger->error('Bulk assignments create error: wrong type.', ['operation' => $operation]);
                $result->addBulkOperationFailure($operation);

                continue;
            }

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

                $result->addBulkOperationSuccess($operation);

                $this->logBuffer[] = [
                    'message' => sprintf(
                        "Successful assignment create operation for user with username='%s'.",
                        $user->getUsername()
                    ),
                    'lineItem' => $newAssignment->getLineItem(),
                ];
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Bulk assignments create error: ' . $exception->getMessage(),
                    ['operation' => $operation]
                );
                $result->addBulkOperationFailure($operation);
            }
        }

        if (!$result->hasFailures()) {
            $this->entityManager->flush();
            $this->entityManager->commit();

            foreach ($this->logBuffer as $logRecord) {
                $this->logger->info(
                    $logRecord['message'],
                    ['lineItem' => $logRecord['lineItem']]
                );
            }
        } else {
            $this->entityManager->rollback();
        }

        return $result;
    }
}
