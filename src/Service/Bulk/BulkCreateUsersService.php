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

use OAT\SimpleRoster\DataTransferObject\UserCreationResult;
use OAT\SimpleRoster\DataTransferObject\UserCreationResultMessage;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Factory\LineItemCriteriaFactory;
use OAT\SimpleRoster\Lti\Service\ColumnGroupResolver;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Lti\Service\GroupResolverInterface;
use OAT\SimpleRoster\Lti\Service\StateDrivenUserGenerator;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Lti\Service\AssigmentFactoryInterface;
use OAT\SimpleRoster\Storage\UserGenerator\StorageInterface;

class BulkCreateUsersService
{
    private array $lineItemSlugs = [];

    private LineItemRepository $lineItemRepository;
    private AssignmentRepository $assignmentRepository;
    private GenerateGroupIdsService $generateGroupIdsService;
    private LtiInstanceRepository $ltiInstanceRepository;
    private UserCreationResultMessage $userCreationMessage;
    private LineItemCriteriaFactory $lineItemCriteriaFactory;
    private AssigmentFactoryInterface $assigmentFactory;
    private StorageInterface $storage;

    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    public function __construct(
        LineItemRepository        $lineItemRepository,
        AssignmentRepository      $assignmentRepository,
        GenerateGroupIdsService   $generateGroupIdsService,
        LtiInstanceRepository     $ltiInstanceRepository,
        UserCreationResultMessage $userCreationMessage,
        LineItemCriteriaFactory   $lineItemCriteriaFactory,
        AssigmentFactoryInterface $assigmentFactory,
        StorageInterface          $storage
    )
    {
        $this->lineItemRepository = $lineItemRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->generateGroupIdsService = $generateGroupIdsService;
        $this->userCreationMessage = $userCreationMessage;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->lineItemCriteriaFactory = $lineItemCriteriaFactory;
        $this->assigmentFactory = $assigmentFactory;
        $this->storage = $storage;
    }

    /**
     * @throws LineItemNotFoundException
     */
    public function createUsers(
        array   $lineItemIds,
        array   $lineItemSlugs,
        array   $userPrefixes,
        int     $batchSize,
        ?string $groupPrefix
    ): UserCreationResult
    {
        $notExistLineItemsArray = $userGroupIds = [];

        if (!empty($lineItemIds) || !empty($lineItemSlugs)) {
            $criteria = $this->lineItemCriteriaFactory->create($lineItemIds, $lineItemSlugs);
            $lineItems = $this->lineItemRepository->findLineItemsByCriteria($criteria)->jsonSerialize();
            if (empty($lineItems)) {
                $exceptionMessage = $lineItemIds
                    ? implode(',', $lineItemIds) . ' Line item id(s) not exist in the system'
                    : implode(',', $lineItemSlugs) . ' Line item slug(s) not exist in the system';
                throw new LineItemNotFoundException($exceptionMessage);
            }

            $this->setLineItemSlugData($lineItems);
            $notExistLineItemsArray = $lineItemIds
                ? array_diff($lineItemIds, array_keys($this->lineItemSlugs))
                : array_diff($lineItemSlugs, array_values($this->lineItemSlugs));
        }

        if (empty($lineItemIds) && empty($lineItemSlugs)) {
            $this->getAllLineItemSlugs();
        }

        $userNameLastIndexes = $this->getLastUserAssignedToLineItems($this->lineItemSlugs);

        $userGroupAssignCount = 0;
        if ($groupPrefix) {
            $userGroupIds = $this->generateGroupIdsService->generateGroupIds(
                $groupPrefix,
                $this->ltiInstanceRepository->findAllAsCollection()
            );
            $userGroupAssignCount = (int)ceil(
                count($userPrefixes) * count($this->lineItemSlugs) * $batchSize / count($userGroupIds)
            );
        }

        $resolver = $groupPrefix
            ? new ColumnGroupResolver($userGroupIds, $userGroupAssignCount)
            : null;

        $slugTotalUsers = $this->createBulkUserAssignmentData(
            $userNameLastIndexes,
            $userPrefixes,
            $batchSize,
            $resolver
        );

        $message = $this->userCreationMessage->normalizeMessage($slugTotalUsers, $userPrefixes);

        return new UserCreationResult($message, $notExistLineItemsArray);
    }

    private function setLineItemSlugData(array $lineItems): void
    {
        $lineItemSlugs = [];
        foreach ($lineItems as $lineItem) {
            $lineItemSlugs[$lineItem->getId()] = $lineItem->getSlug();
        }
        $this->lineItemSlugs = $lineItemSlugs;
    }

    private function createBulkUserAssignmentData(
        array                   $userNameLastIndexes,
        array                   $userPrefixes,
        int                     $batchSize,
        ?GroupResolverInterface $groupResolver
    ): array
    {
        $slugWiseTotalUsersArray = array_fill_keys($this->lineItemSlugs, 0);
        $data = date('Y-m-d');

        foreach ($userPrefixes as $prefix) {
            $csvPath = sprintf('%s/%s', $data, $prefix);

            foreach ($this->lineItemSlugs as $lineSlug) {
                $csvFilename = sprintf('%s-%s.csv', $lineSlug, $prefix);

                $generator = new StateDrivenUserGenerator(
                    $lineSlug,
                    $prefix,
                    (int)$userNameLastIndexes[$lineSlug] + 1,
                    $groupResolver
                );

                $users = $generator->makeBatch($batchSize);

                $assignments = $this->assigmentFactory->fromUsersWithLineItem($users, $lineSlug);

                $this->storage->persistUsers(sprintf('%s/%s', $csvPath, $csvFilename), $users);
                $this->storage->persistAssignments(sprintf('%s/Assignments-%s.csv', $csvPath, $csvFilename), $assignments);

                $this->storage->persistUsers(sprintf('%s/users_aggregated.csv', $data), $users);
                $this->storage->persistAssignments(sprintf('%s/assignments_aggregated.csv', $data), $assignments);


                $slugWiseTotalUsersArray[$lineSlug] += count($users);
            }
        }

        return $slugWiseTotalUsersArray;
    }

    /**
     * Function to get all slug data
     * @throws LineItemNotFoundException
     */
    private function getAllLineItemSlugs(): void
    {
        $lineItems = $this->lineItemRepository->findAllAsCollection()->jsonSerialize();
        if (empty($lineItems)) {
            throw new LineItemNotFoundException('No line items were found in database.');
        }

        $this->setLineItemSlugData($lineItems);
    }

    private function getLastUserAssignedToLineItems(array $lineItemSlugs): array
    {
        $userNameIncrementArray = [];

        foreach ($lineItemSlugs as $slugKey => $slug) {
            $assignment = $this->assignmentRepository->findByLineItemId($slugKey);
            $userNameIncrementArray[$slug] = self::DEFAULT_USERNAME_INCREMENT_VALUE;

            if (!empty($assignment)) {
                $userData = $assignment->getUser()->getUsername();
                $userNameArray = explode('_', (string)$userData);
                $userNameLastIndex = self::DEFAULT_USERNAME_INCREMENT_VALUE;
                $index = end($userNameArray);
                if (count($userNameArray) > 0 && is_numeric($index)) {
                    $userNameLastIndex = (int)$index;
                }
                $userNameIncrementArray[$slug] = $userNameLastIndex;
            }
        }

        return $userNameIncrementArray;
    }
}
