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

use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Ingester\AssignmentIngester;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Repository\NativeUserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use Symfony\Component\Filesystem\Filesystem;
use OAT\SimpleRoster\Lti\Factory\GroupIdLoadBalancerFactory;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\DataTransferObject\UserCreationResponse;
use OAT\SimpleRoster\Csv\CsvWriter;

class BulkUserCreationService
{
    /** @var int[] */
    private array $lineItemIds = [];

    /** @var string[] */
    private array $lineItemSlugs = [];

    /** @var int */
    private int $userGroupBatchCount = 0;
    private int $groupIndex = 0;
    private string $generatedUsersFilePath;

    /** @var array */
    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    private LineItemRepository $lineItemRepository;
    private AssignmentRepository $assignmentRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private NativeUserRepository $userRepository;
    private AssignmentIngester $assignmentIngester;
    private Filesystem $filesystem;
    private GroupIdLoadBalancerFactory $groupIdLoadBalancerFactory;
    private UserCreationResponse $userCreationResponse;
    private CsvWriter $csvWriter;

    public function __construct(
        LineItemRepository $lineItemRepository,
        AssignmentRepository $assignmentRepository,
        UserPasswordHasherInterface $passwordHasher,
        NativeUserRepository $userRepository,
        AssignmentIngester $assignmentIngester,
        GroupIdLoadBalancerFactory $groupIdLoadBalancerFactory,
        UserCreationResponse $userCreationResponse,
        Filesystem $filesystem,
        CsvWriter $csvWriter,
        string $generatedUsersFilePath
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->assignmentIngester = $assignmentIngester;
        $this->filesystem = $filesystem;
        $this->groupIdLoadBalancerFactory = $groupIdLoadBalancerFactory;
        $this->userCreationResponse = $userCreationResponse;
        $this->csvWriter = $csvWriter;
        $this->generatedUsersFilePath = $generatedUsersFilePath;
    }

    /**
     * @throws LineItemNotFoundException
     */
    public function createUsers(
        array $lineItemIds,
        array $lineItemSlugs,
        array $userPrefix,
        int $batchSize,
        ?string $groupPrefix
    ): array {

        $notExistLineItemsArray = $userGroupIds = [];

        if (!empty($lineItemIds) || !empty($lineItemSlugs)) {
            $this->lineItemIds = $lineItemIds;
            $this->lineItemSlugs = $lineItemSlugs;
            $criteria = $this->getFindLineItemCriteria();
            $lineItemsArray = ($this->lineItemRepository->findLineItemsByCriteria($criteria))->jsonSerialize();
            if (empty($lineItemsArray)) {
                $exceptionMessage = $this->lineItemIds
                    ? implode(',', $this->lineItemIds) . ' LineItem Ids not exist in the system'
                    : implode(',', $this->lineItemSlugs) . ' LineItem Slugs not exist in the system';
                throw new LineItemNotFoundException($exceptionMessage);
            }

            $lineItemSlugArray = $this->generateSlugData($lineItemsArray);
            $notExistLineItemsArray = $this->lineItemIds
                ? array_diff($lineItemIds, array_keys($lineItemSlugArray))
                : array_diff($lineItemSlugs, array_values($lineItemSlugArray));
        }

        if (empty($lineItemIds) && empty($lineItemSlugs)) {
            $this->getAllLineItemSlugs();
        }

        $userIncrNo = $this->getLastUserAssignedToLineItems($this->lineItemSlugs);

        $userGroupAssignCount = 0;
        if ($groupPrefix) {
            $userGroupIds = $this->groupIdLoadBalancerFactory->getLoadBalanceGroupID($groupPrefix);
            $userGroupAssignCount = (int) ceil(
                count($userPrefix) * count($this->lineItemSlugs) * $batchSize / count($userGroupIds)
            );
        }

        $slugTotalUsers = $this->createBulkUserAssignmentData(
            $userIncrNo,
            $userGroupIds,
            $userPrefix,
            $userGroupAssignCount,
            $groupPrefix,
            $batchSize
        );

        return $this->userCreationResponse->userCreationResult(
            $slugTotalUsers,
            $notExistLineItemsArray,
            $userPrefix
        );
    }

    private function generateSlugData(array $lineData): array
    {
        $lineItemArray = [];
        foreach ($lineData as $lineItem) {
            $lineItemArray[$lineItem->getId()] = $lineItem->getSlug();
        }
        $this->lineItemSlugs = $lineItemArray;

        return $lineItemArray;
    }

    private function createBulkUserAssignmentData(
        array $userIncrNo,
        array $userGroupIds,
        array $userPrefix,
        int $userGroupAssignCount,
        ?string $groupPrefix,
        int $batchSize
    ): array {
        $slugWiseTotalUsersArray = [];
        $automateCsvPath = $this->generatedUsersFilePath . date('Y-m-d');
        $userCsvHead = ['username','password','groupId'];
        $assignmentCsvHead = ['username','lineItemSlug'];

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
                $noOfUsersCreated = 0;
                $csvFilename = sprintf('%s-%s.csv', $lineSlugs, $prefix);
                $csvData = $assignmentCsvData = [];
                foreach (range(1, $batchSize) as $inc) {
                    $username = sprintf('%s_%s_%d', $lineSlugs, $prefix, ((int)$userIncrNo[$lineSlugs] + $inc));
                    $userPassword = $this->createUserPassword();
                    $userGroupId = $groupPrefix
                        ? $this->createUserGroupId($userGroupIds, $userGroupAssignCount)
                        : '';
                    $csvData[] = [$username, $userPassword,$userGroupId];
                    $assignmentCsvData[] = [$username, $lineSlugs];
                    $userDtoCollection->add($this->createUserDto($username, $userPassword, $userGroupId));
                    $assignmentDtoCollection->add($this->createAssignmentDto($lineKey, $username));
                    $noOfUsersCreated++;
                }
                $this->csvWriter->writeCsv(sprintf('%s/%s', $csvPath, $csvFilename), $userCsvHead, $csvData);
                $this->csvWriter->writeCsv(
                    sprintf('%s/Assignments-%s-%s.csv', $csvPath, $lineSlugs, $prefix),
                    $assignmentCsvHead,
                    $assignmentCsvData
                );
                $this->csvWriter->writeCsv(
                    sprintf('%s/users_aggregated.csv', $automateCsvPath),
                    $userCsvHead,
                    $csvData
                );
                $this->csvWriter->writeCsv(
                    sprintf('%s/assignments_aggregated.csv', $automateCsvPath),
                    $assignmentCsvHead,
                    $assignmentCsvData
                );
                $slugWiseTotalUsersArray[$lineSlugs] = array_key_exists($lineSlugs, $slugWiseTotalUsersArray)
                    ? $slugWiseTotalUsersArray[$lineSlugs] +  $noOfUsersCreated
                    : $noOfUsersCreated;
            }
        }

        if (!$userDtoCollection->isEmpty() && !$assignmentDtoCollection->isEmpty()) {
            $this->userRepository->insertMultiple($userDtoCollection);
            $this->assignmentIngester->ingest($assignmentDtoCollection);
        }

        return $slugWiseTotalUsersArray;
    }

    private function createUserPassword(): string
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstvwxyz'), 0, 8);
    }

    private function createUserDto(string $username, string $userPassword, string $userGroupId): UserDto
    {
        return new UserDto(
            $username,
            $this->passwordHasher->hashPassword(new User(), $userPassword),
            $userGroupId ? $userGroupId : null
        );
    }

    private function createAssignmentDto(
        int $lineKey,
        string $username
    ): AssignmentDto {

        return new AssignmentDto(Assignment::STATE_READY, $lineKey, $username);
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

    private function getLastUserAssignedToLineItems(
        array $lineItemSlugs
    ): array {
        $userNameIncArr = [];
        foreach ($lineItemSlugs as $slugKey => $slug) {
            $assignment = $this->assignmentRepository->findByLineItemId($slugKey);
            $userNameIncArr[$slug] = self::DEFAULT_USERNAME_INCREMENT_VALUE;
            if (!empty($assignment)) {
                $userInfo = $assignment->getUser()->getUsername();
                $userNameArray = explode('_', (string)$userInfo);
                $userNameLastNo = preg_match('/^\d+$/', end($userNameArray))
                    ? (int)end($userNameArray)
                    : self::DEFAULT_USERNAME_INCREMENT_VALUE;
                $userNameIncArr[$slug] = $userNameLastNo;
            }
        }

        return $userNameIncArr;
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
