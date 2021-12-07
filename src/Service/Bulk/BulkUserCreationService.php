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

use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use Symfony\Component\Filesystem\Filesystem;
use OAT\SimpleRoster\Lti\Factory\GroupIdLoadBalancerFactory;
use OAT\SimpleRoster\Ingester\UserAssignmentIngester;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\DataTransferObject\UserCreationResult;
use OAT\SimpleRoster\Csv\BulkUserCreationCsvWriter;
use Psr\Log\LoggerInterface;

class BulkUserCreationService
{
    private array $lineItemIds = [];
    private array $lineItemSlugs = [];

    private int $userGroupBatchCount = 0;
    private int $groupIndex = 0;

    private string $generatedUsersFilePath;

    private LineItemRepository $lineItemRepository;
    private AssignmentRepository $assignmentRepository;
    private Filesystem $filesystem;
    private GroupIdLoadBalancerFactory $groupIdLoadBalancerFactory;
    private UserAssignmentIngester $userAssignmentIngester;
    private BulkUserCreationCsvWriter $csvWriter;
    private LoggerInterface $logger;

    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    public function __construct(
        LineItemRepository $lineItemRepository,
        AssignmentRepository $assignmentRepository,
        GroupIdLoadBalancerFactory $groupIdLoadBalancerFactory,
        UserAssignmentIngester $userAssignmentIngester,
        BulkUserCreationCsvWriter $csvWriter,
        LoggerInterface $logger,
        Filesystem $filesystem,
        string $generatedUsersFilePath
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->groupIdLoadBalancerFactory = $groupIdLoadBalancerFactory;
        $this->userAssignmentIngester = $userAssignmentIngester;
        $this->csvWriter = $csvWriter;
        $this->logger = $logger;
        $this->generatedUsersFilePath = $generatedUsersFilePath;
        $this->filesystem = $filesystem;
    }

    /**
     * @throws LineItemNotFoundException
     */
    public function createUsers(
        array $lineItemIds,
        array $lineItemSlugs,
        array $userPrefixes,
        int $batchSize,
        ?string $groupPrefix
    ): UserCreationResult {

        $notExistLineItemsArray = $userGroupIds = [];

        if (!empty($lineItemIds) || !empty($lineItemSlugs)) {
            $this->lineItemIds = $lineItemIds;
            $this->lineItemSlugs = $lineItemSlugs;
            $criteria = $this->getFindLineItemCriteria();
            $lineItems = $this->lineItemRepository->findLineItemsByCriteria($criteria)->jsonSerialize();
            if (empty($lineItems)) {
                $exceptionMessage = $this->lineItemIds
                    ? implode(',', $this->lineItemIds) . ' Line item id(s) not exist in the system'
                    : implode(',', $this->lineItemSlugs) . ' Line item slug(s) not exist in the system';
                throw new LineItemNotFoundException($exceptionMessage);
            }

            $this->generateSlugData($lineItems);
            $notExistLineItemsArray = $this->lineItemIds
                ? array_diff($lineItemIds, array_keys($this->lineItemSlugs))
                : array_diff($lineItemSlugs, array_values($this->lineItemSlugs));
        }

        if (empty($lineItemIds) && empty($lineItemSlugs)) {
            $this->getAllLineItemSlugs();
        }

        $userNameLastIndexes = $this->getLastUserAssignedToLineItems($this->lineItemSlugs);

        $userGroupAssignCount = 0;
        if ($groupPrefix) {
            $userGroupIds = $this->groupIdLoadBalancerFactory->getLoadBalanceGroupID($groupPrefix);
            $userGroupAssignCount = (int) ceil(
                count($userPrefixes) * count($this->lineItemSlugs) * $batchSize / count($userGroupIds)
            );
        }

        $slugTotalUsers = $this->createBulkUserAssignmentData(
            $userNameLastIndexes,
            $userGroupIds,
            $userPrefixes,
            $userGroupAssignCount,
            $groupPrefix,
            $batchSize
        );

        $message = $this->getOperationResultMessage($slugTotalUsers, $userPrefixes);

        return new UserCreationResult(1, $message, $notExistLineItemsArray);
    }

    public function getOperationResultMessage(array $slugTotalUsers, array $userPrefix): string
    {
        $message = '';
        $userPrefixString = implode(',', $userPrefix);

        foreach ($slugTotalUsers as $slug => $totalUsers) {
            $message .= sprintf(
                "%s users created for line item %s for user prefix %s \n",
                $totalUsers,
                $slug,
                $userPrefixString
            );
        }

        return $message;
    }


    private function generateSlugData(array $lineItems): void
    {
        $lineItemSlugs = [];
        foreach ($lineItems as $lineItem) {
            $lineItemSlugs[$lineItem->getId()] = $lineItem->getSlug();
        }
        $this->lineItemSlugs = $lineItemSlugs;
    }

    private function createBulkUserAssignmentData(
        array $userNameLastIndexes,
        array $userGroupIds,
        array $userPrefix,
        int $userGroupAssignCount,
        ?string $groupPrefix,
        int $batchSize
    ): array {
        $slugWiseTotalUsersArray = [];
        $automateCsvPath = $this->generatedUsersFilePath . date('Y-m-d');

        $userDtoCollection = new UserDtoCollection();
        $assignmentDtoCollection = new AssignmentDtoCollection();

        if (!$this->filesystem->exists($automateCsvPath)) {
            $this->filesystem->mkdir($automateCsvPath);
        }

        foreach ($userPrefix as $prefix) {
            $csvPath = sprintf('%s/%s', $automateCsvPath, $prefix);
            if (!$this->filesystem->exists($csvPath)) {
                $this->filesystem->mkdir($csvPath);
            }

            foreach ($this->lineItemSlugs as $lineKey => $lineSlugs) {
                $slugTotalUsersCreated = 0;
                $csvFilename = sprintf('%s-%s.csv', $lineSlugs, $prefix);
                $csvData = $assignmentCsvData = [];
                foreach (range(1, $batchSize) as $batchIncremntValue) {
                    $username = sprintf(
                        '%s_%s_%d',
                        $lineSlugs,
                        $prefix,
                        (
                            (int)$userNameLastIndexes[$lineSlugs] + $batchIncremntValue
                        )
                    );
                    $userPassword = $this->createUserPassword();
                    $userGroupId = $groupPrefix
                        ? $this->createUserGroupId($userGroupIds, $userGroupAssignCount)
                        : '';

                    $csvData[] = [$username, $userPassword,$userGroupId];
                    $assignmentCsvData[] = [$username, $lineSlugs];

                    $this->userAssignmentIngester->createUserDtoCollection(
                        $userDtoCollection,
                        $username,
                        $userPassword,
                        $userGroupId
                    );
                    $this->userAssignmentIngester->createAssignmentDtoCollection(
                        $assignmentDtoCollection,
                        $lineKey,
                        $username
                    );
                    $this->logger->info(
                        sprintf(
                            'User %s has been created and assigned to line item %s successfully',
                            $username,
                            $lineSlugs
                        )
                    );
                    $slugTotalUsersCreated++;
                }

                $this->csvWriter->writeCsvData(
                    $lineSlugs,
                    $prefix,
                    $csvPath,
                    $csvFilename,
                    $automateCsvPath,
                    $csvData,
                    $assignmentCsvData
                );

                $slugWiseTotalUsersArray[$lineSlugs] = array_key_exists($lineSlugs, $slugWiseTotalUsersArray)
                    ? $slugWiseTotalUsersArray[$lineSlugs] +  $slugTotalUsersCreated
                    : $slugTotalUsersCreated;
            }
        }
        $this->userAssignmentIngester->saveBulkUserAssignmentData($userDtoCollection, $assignmentDtoCollection);

        return $slugWiseTotalUsersArray;
    }

    private function createUserPassword(): string
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstvwxyz'), 0, 8);
    }

    private function getFindLineItemCriteria(): FindLineItemCriteria
    {
        $criteria = new FindLineItemCriteria();

        if (!empty($this->lineItemIds)) {
            $criteria->addLineItemIds(...$this->lineItemIds);
        }

        if (!empty($this->lineItemSlugs)) {
            $criteria->addLineItemSlugs(...$this->lineItemSlugs);
        }

        return $criteria;
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

        $this->generateSlugData($lineItems);
    }

    private function getLastUserAssignedToLineItems(array $lineItemSlugs): array
    {
        $userNameIncrementArray = [];

        foreach ($lineItemSlugs as $slugKey => $slug) {
            $assignment = $this->assignmentRepository->findByLineItemId($slugKey);
            $userNameIncrementArray[$slug] = self::DEFAULT_USERNAME_INCREMENT_VALUE;

            if (!empty($assignment)) {
                $userData = $assignment->getUser()->getUsername();
                $userNameArray = explode('_', (string) $userData);
                $userNameLastIndex = preg_match('/^\d+$/', end($userNameArray))
                    ? (int)end($userNameArray)
                    : self::DEFAULT_USERNAME_INCREMENT_VALUE;

                $userNameIncrementArray[$slug] = $userNameLastIndex;
            }
        }

        return $userNameIncrementArray;
    }

    private function createUserGroupId(array $userGroupIds, int $userGroupAssignCount): string
    {
        $userGroupId = '';
        if ($userGroupAssignCount !== 0) {
            if ($this->userGroupBatchCount < $userGroupAssignCount) {
                $userGroupId = $userGroupIds[$this->groupIndex];
                $this->userGroupBatchCount++;

                return $userGroupId;
            }

            $this->userGroupBatchCount = 1;
            $this->groupIndex++;
            $userGroupId = $userGroupIds[$this->groupIndex];
        }

        return $userGroupId;
    }
}
