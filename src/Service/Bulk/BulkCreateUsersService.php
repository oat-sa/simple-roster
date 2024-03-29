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

use Doctrine\DBAL\Exception;
use Doctrine\ORM\OptimisticLockException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Lti\Service\AssignmentCollectionMapper\AssignmentCollectionMapperInterface;
use OAT\SimpleRoster\Lti\Service\AssigmentFactoryInterface;
use OAT\SimpleRoster\Lti\Service\GroupResolverInterface;
use OAT\SimpleRoster\Lti\Service\StateDrivenUserGenerator;
use OAT\SimpleRoster\Lti\Service\UserGenerator\UserGeneratorStateStorageInterface;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Service\LineItem\LineItemAssignedIndexResolver;
use OAT\SimpleRoster\Storage\UserGenerator\StorageInterface;

class BulkCreateUsersService
{
    private AssigmentFactoryInterface $assigmentFactory;
    private StorageInterface $storage;
    private CreateUserServiceContext $createUserServiceContext;
    private UserGeneratorStateStorageInterface $userStateStorage;
    private AssignmentCollectionMapperInterface $assignmentMapper;
    private LineItemAssignedIndexResolver $lineItemAssignedIndexResolver;
    private LineItemRepository $lineItemRepository;

    public function __construct(
        UserGeneratorStateStorageInterface $userStateStorage,
        AssignmentCollectionMapperInterface $assignmentMapper,
        LineItemAssignedIndexResolver $lineItemAssignedIndexResolver,
        AssigmentFactoryInterface $assigmentFactory,
        StorageInterface $storage,
        CreateUserServiceContext $createUserServiceContext,
        LineItemRepository $itemRepository
    ) {
        $this->assigmentFactory = $assigmentFactory;
        $this->storage = $storage;
        $this->createUserServiceContext = $createUserServiceContext;
        $this->userStateStorage = $userStateStorage;
        $this->assignmentMapper = $assignmentMapper;
        $this->lineItemAssignedIndexResolver = $lineItemAssignedIndexResolver;
        $this->lineItemRepository = $itemRepository;
    }

    /**
     * @param LineItem[] $lineItems
     * @param string $path
     * @param CreateUserServiceContext|null $createUserServiceContext
     * @param GroupResolverInterface|null $groupResolver
     *
     * @throws OptimisticLockException
     */
    public function generate(
        array $lineItems,
        string $path,
        ?CreateUserServiceContext $createUserServiceContext = null,
        ?GroupResolverInterface $groupResolver = null
    ): void {
        $createUserServiceContext = $createUserServiceContext ?? $this->createUserServiceContext;

        if (!$createUserServiceContext->isRecreateQAUsers()) {
            $filteredLineItems = $this->filterLineItems($lineItems);
            if (empty($filteredLineItems)) {
                return;
            }
        }

        $userNameLastIndexes = $this->lineItemAssignedIndexResolver->getLastUserAssignedToLineItems($lineItems);

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
                $users = $this->userStateStorage->insertUsers($generatedUsers);

                $assignments = $this->assigmentFactory->fromUsersWithLineItem($users, $lineItem);

                $this->assignmentMapper->insertAssignment($assignments);

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
     *
     * @throws Exception
     */
    private function filterLineItems(array $lineItems): array
    {
        return array_filter(
            $lineItems,
            fn($lineItem) => !$this->lineItemRepository->hasLineItemQAUsers($lineItem)
        );
    }
}
