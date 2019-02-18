<?php declare(strict_types=1);

namespace App\Service\Bulk;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use App\Bulk\Result\BulkResult;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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

        $this->entityManager->beginTransaction();

        foreach ($operationCollection as $operation) {
            if ($operation->getType() === BulkOperation::TYPE_UPDATE) {
                try {
                    /** @var User $user */
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->getByUsernameWithAssignments($operation->getIdentifier());

                    foreach ($user->getAvailableAssignments() as $assignment) {
                        $assignment->setState($operation->getAttribute('state'));

                        $this->logBuffer[] = sprintf(
                            'Successful assignment update operation (id=`%s`) for user with username=`%s`.',
                            $assignment->getId(),
                            $user->getUsername()
                        );
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

            foreach ($this->logBuffer as $logRecord) {
                $this->logger->info($logRecord);
            }
        } else {
            $this->entityManager->rollback();
        }

        return $result;
    }
}
