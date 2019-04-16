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
use LogicException;
use Psr\Log\LoggerInterface;
use Throwable;

class BulkUpdateUsersAssignmentsStateService implements BulkOperationCollectionProcessorInterface
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

        if (!$operationCollection->isDryRun()) {
            $this->entityManager->beginTransaction();
        }

        foreach ($operationCollection as $operation) {
            if ($operation->getType() !== BulkOperation::TYPE_UPDATE) {
                $this->logger->error('Bulk assignments cancel error: wrong type.', ['operation' => $operation]);
                $result->addBulkOperationFailure($operation);

                continue;
            }

            if ($operation->getAttribute('state') !== Assignment::STATE_CANCELLED) {
                throw new LogicException(
                    sprintf(
                        "Not allowed state attribute received while bulk updating: '%s', '%s' expected.",
                        $operation->getAttribute('state'),
                        Assignment::STATE_CANCELLED
                    )
                );
            }

            try {
                /** @var UserRepository $userRepository */
                $userRepository = $this->entityManager->getRepository(User::class);
                $user = $userRepository->getByUsernameWithAssignments($operation->getIdentifier());

                foreach ($user->getAssignments() as $assignment) {
                    if (!in_array(
                        $assignment->getState(),
                        [Assignment::STATE_READY, Assignment::STATE_STARTED],
                        true
                    )) {
                        continue;
                    }

                    $assignment->setState(Assignment::STATE_CANCELLED);

                    $this->logBuffer[] = [
                        'message' => sprintf(
                            "Successful assignment cancellation (assignmentId = '%s', username = '%s').",
                            $assignment->getId(),
                            $user->getUsername()
                        ),
                        'lineItem' => $assignment->getLineItem(),
                    ];
                }

                $result->addBulkOperationSuccess($operation);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Bulk assignments cancellation error: ' . $exception->getMessage(),
                    ['operation' => $operation]
                );
                $result->addBulkOperationFailure($operation);
            }
        }

        return $this->processResult($operationCollection, $result);
    }

    private function processResult(BulkOperationCollection $operationCollection, BulkResult $result): BulkResult
    {
        if (!$result->hasFailures() && !$operationCollection->isDryRun()) {
            $this->entityManager->flush();
            $this->entityManager->commit();

            foreach ($this->logBuffer as $logRecord) {
                $this->logger->info(
                    $logRecord['message'],
                    ['lineItem' => $logRecord['lineItem']]
                );
            }
        } elseif (!$operationCollection->isDryRun()) {
            $this->entityManager->rollback();
        }

        return $result;
    }
}
