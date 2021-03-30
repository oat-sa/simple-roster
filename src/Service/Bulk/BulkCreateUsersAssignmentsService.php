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
use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Bulk\Processor\BulkOperationCollectionProcessorInterface;
use OAT\SimpleRoster\Bulk\Result\BulkResult;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use Psr\Log\LoggerInterface;
use Throwable;

class BulkCreateUsersAssignmentsService implements BulkOperationCollectionProcessorInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var UserRepository */
    private $userRepository;

    /** @var UserCacheWarmerService */
    private $userCacheWarmerService;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $logBuffer = [];

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserCacheWarmerService $userCacheWarmerService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->userCacheWarmerService = $userCacheWarmerService;
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
                $this->processOperation($operation, $result);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Bulk assignments create error: ' . $exception->getMessage(),
                    ['operation' => $operation]
                );
                $result->addBulkOperationFailure($operation);
            }
        }

        return $this->processResult($result);
    }

    /**
     * @throws EntityNotFoundException
     */
    private function processOperation(BulkOperation $operation, BulkResult $result): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments($operation->getIdentifier());

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

        $this->userCacheWarmerService->process(
            new UsernameCollection(...$result->getSuccessfulBulkOperationIdentifiers())
        );

        return $result;
    }
}
