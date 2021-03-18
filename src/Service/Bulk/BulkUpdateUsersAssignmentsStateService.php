<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Bulk;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use LogicException;
use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use OAT\SimpleRoster\Bulk\Result\BulkResult;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
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
            if ($operation->getType() !== BulkOperation::TYPE_UPDATE) {
                $this->logger->error('Bulk assignments cancel error: wrong type.', ['operation' => $operation]);
                $result->addBulkOperationFailure($operation);

                continue;
            }

            $this->validateStateTransition($operation);

            try {
                $this->processOperation($operation, $result);
            } catch (Throwable $exception) {
                $this->logger->error('Bulk assignments cancellation error: ' . $exception->getMessage());
                $result->addBulkOperationFailure($operation);
            }
        }

        return $this->processResult($result);
    }

    private function processResult(BulkResult $result): BulkResult
    {
        if ($result->hasFailures()) {
            $this->entityManager->rollback();

            return $result;
        }

        $this->entityManager->flush();
        $this->entityManager->commit();

        foreach ($this->logBuffer as $logRecord) {
            $this->logger->info(
                $logRecord['message'],
                ['lineItem' => $logRecord['lineItem']]
            );
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
        $user = $userRepository->findByUsernameWithAssignments($operation->getIdentifier());

        foreach ($user->getCancellableAssignments() as $assignment) {
            $assignment->setState(Assignment::STATE_CANCELLED);

            $this->logBuffer[] = [
                'message' => sprintf(
                    "Successful assignment cancellation (assignmentId = '%s', username = '%s').",
                    (string)$assignment->getId(),
                    $user->getUsername()
                ),
                'lineItem' => $assignment->getLineItem(),
            ];
        }

        $result->addBulkOperationSuccess($operation);
    }

    /**
     * @throws LogicException
     */
    private function validateStateTransition(BulkOperation $operation): void
    {
        if ($operation->getAttribute('state') !== Assignment::STATE_CANCELLED) {
            throw new LogicException(
                sprintf(
                    "Not allowed state attribute received while bulk updating: '%s', '%s' expected.",
                    $operation->getAttribute('state'),
                    Assignment::STATE_CANCELLED
                )
            );
        }
    }
}
