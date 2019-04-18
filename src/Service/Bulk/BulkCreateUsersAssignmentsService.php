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
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Log\LoggerInterface;
use Throwable;

class BulkCreateUsersAssignmentsService implements BulkOperationCollectionProcessorInterface
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
            if ($operation->getType() !== BulkOperation::TYPE_CREATE) {
                $this->logger->error('Bulk assignments create error: wrong type.', ['operation' => $operation]);
                $result->addBulkOperationFailure($operation);

                continue;
            }

            try {
                $this->processOperation($operation, $result);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Bulk assignments create error: ' . $exception->getMessage(),
                    ['operation' => $operation]
                );
                $result->addBulkOperationFailure($operation);
            }
        }

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

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    private function processOperation(BulkOperation $operation, BulkResult $result): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments($operation->getIdentifier());

        $lastAssignment = $user->getLastAssignment();

        foreach ($user->getAssignments() as $assignment) {
            if ($assignment->isCancellable()) {
                $assignment->setState(Assignment::STATE_CANCELLED);
            }
        }

        $newAssignment = (new Assignment())
            ->setState(Assignment::STATE_READY)
            ->setLineItem($lastAssignment->getLineItem());

        $user->addAssignment($newAssignment);

        $this->entityManager->persist($newAssignment);

        $result->addBulkOperationSuccess($operation);

        $this->logBuffer[] = [
            'message' => sprintf(
                "Successful assignment creation (username = '%s').",
                $user->getUsername()
            ),
            'lineItem' => $newAssignment->getLineItem(),
        ];
    }
}
