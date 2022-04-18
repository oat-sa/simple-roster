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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Bulk;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Lti\Service\AssigmentFactoryInterface;
use OAT\SimpleRoster\Lti\Service\GroupResolverInterface;
use OAT\SimpleRoster\Lti\Service\StateDrivenUserGenerator;
use OAT\SimpleRoster\Lti\Service\UserGenerator\UserGeneratorStateStorageInterface;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Storage\UserGenerator\StorageInterface;

class BulkCreateUsersService
{
    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    private AssignmentRepository $assignmentRepository;
    private AssigmentFactoryInterface $assigmentFactory;
    private StorageInterface $storage;
    private CreateUserServiceContext $createUserServiceContext;
    private UserGeneratorStateStorageInterface $stateStorage;

    public function __construct(
        UserGeneratorStateStorageInterface $stateStorage,
        AssignmentRepository $assignmentRepository,
        AssigmentFactoryInterface $assigmentFactory,
        StorageInterface $storage,
        CreateUserServiceContext $createUserServiceContext
    ) {
        $this->assignmentRepository = $assignmentRepository;
        $this->assigmentFactory = $assigmentFactory;
        $this->storage = $storage;
        $this->createUserServiceContext = $createUserServiceContext;
        $this->stateStorage = $stateStorage;
    }

    /**
     * @param LineItem[] $lineItems
     * @param string $path
     * @param CreateUserServiceContext|null $createUserServiceContext
     * @param GroupResolverInterface|null $groupResolver
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function generate(
        array $lineItems,
        string $path,
        ?CreateUserServiceContext $createUserServiceContext = null,
        ?GroupResolverInterface $groupResolver = null
    ): void {
        $createUserServiceContext = $createUserServiceContext ?? $this->createUserServiceContext;
        $userNameLastIndexes = $this->getLastUserAssignedToLineItems($lineItems);

        foreach ($createUserServiceContext->iteratePrefixes() as $prefixes) {
            $csvPath = sprintf('%s/%s/%s', $path, $prefixes->getGroup(), $prefixes->getPrefix());

            foreach ($lineItems as $lineItem) {
                $slug = $lineItem->getSlug();

                $csvFilename = sprintf('%s-%s-%s.csv', $slug, $prefixes->getGroup(), $prefixes->getPrefix());

                $generator = new StateDrivenUserGenerator(
                    $slug,
                    sprintf('%s-%s', $prefixes->getGroup(), $prefixes->getPrefix()),
                    (int)$userNameLastIndexes[$slug] + 1,
                    $groupResolver
                );

                $generatedUsers = $generator->makeBatch($createUserServiceContext->getBatchSize());
                $users = $this->stateStorage->persistUsers($generatedUsers);

                $assignments = $this->assigmentFactory->fromUsersWithLineItem($users, $lineItem);

                $this->stateStorage->persistAssignment($assignments);

                $this->storage->persistUsers(sprintf('%s/%s', $csvPath, $csvFilename), $generatedUsers);
                $this->storage->persistAssignments(
                    sprintf('%s/Assignments-%s', $csvPath, $csvFilename),
                    $assignments
                );

                $this->storage->persistUsers(sprintf('%s/users_aggregated.csv', $path), $generatedUsers);
                $this->storage->persistAssignments(sprintf('%s/assignments_aggregated.csv', $path), $assignments);
            }
        }
    }

    /**
     * @param LineItem[] $lineItems
     */
    private function getLastUserAssignedToLineItems(array $lineItems): array
    {
        /*
         * TODO: replace with uuid or something similar? current solution is not consistency safe
         * */

        $userNameIncrementArray = [];

        foreach ($lineItems as $lineItem) {
            $assignment = $this->assignmentRepository->findByLineItemId((int)$lineItem->getId());
            $userNameIncrementArray[$lineItem->getSlug()] = self::DEFAULT_USERNAME_INCREMENT_VALUE;

            if (!empty($assignment)) {
                $userData = $assignment->getUser()->getUsername();
                $userNameArray = explode('_', (string)$userData);
                $index = end($userNameArray);
                if (is_numeric($index)) {
                    $userNameIncrementArray[$lineItem->getSlug()] = (int)$index;
                }
            }
        }

        return $userNameIncrementArray;
    }
}
