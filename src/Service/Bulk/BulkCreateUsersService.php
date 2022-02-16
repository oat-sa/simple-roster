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

use OAT\SimpleRoster\Csv\CsvWriter;
use OAT\SimpleRoster\DataTransferObject\UserCreationResult;
use OAT\SimpleRoster\DataTransferObject\UserCreationResultMessage;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Factory\LineItemCriteriaFactory;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use Symfony\Component\Filesystem\Filesystem;

class BulkCreateUsersService
{
    private array $lineItemSlugs = [];

    private int $userGroupBatchCount = 0;
    private int $groupIndex = 0;

    private string $generatedUsersFilePath;
    private string $projectDir;

    private const USER_CSV_HEAD = ['username', 'password', 'groupId'];
    private const ASSIGNMENT_CSV_HEAD = ['username', 'lineItemSlug'];

    private LineItemRepository $lineItemRepository;
    private AssignmentRepository $assignmentRepository;
    private Filesystem $filesystem;
    private GenerateGroupIdsService $generateGroupIdsService;
    private CsvWriter $csvWriter;
    private LtiInstanceRepository $ltiInstanceRepository;
    private UserCreationResultMessage $userCreationMessage;
    private LineItemCriteriaFactory $lineItemCriteriaFactory;
    private FolderSyncService $userFolderSync;

    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    public function __construct(
        LineItemRepository $lineItemRepository,
        AssignmentRepository $assignmentRepository,
        GenerateGroupIdsService $generateGroupIdsService,
        CsvWriter $csvWriter,
        LtiInstanceRepository $ltiInstanceRepository,
        UserCreationResultMessage $userCreationMessage,
        LineItemCriteriaFactory $lineItemCriteriaFactory,
        Filesystem $filesystem,
        string $generatedUsersFilePath,
        string $projectDir,
        FolderSyncService $userFolderSync
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->generateGroupIdsService = $generateGroupIdsService;
        $this->userCreationMessage = $userCreationMessage;
        $this->csvWriter = $csvWriter;
        $this->generatedUsersFilePath = $generatedUsersFilePath;
        $this->filesystem = $filesystem;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->lineItemCriteriaFactory = $lineItemCriteriaFactory;
        $this->projectDir = $projectDir;
        $this->userFolderSync = $userFolderSync;
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

        $this->userFolderSync->sync(date('Y-m-d'));

        $message = $this->userCreationMessage->normalizeMessage($slugTotalUsers, $userPrefixes);

        return new UserCreationResult($message, $notExistLineItemsArray, 'test');
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
        array $userNameLastIndexes,
        array $userGroupIds,
        array $userPrefixes,
        int $userGroupAssignCount,
        ?string $groupPrefix,
        int $batchSize
    ): array {
        $slugWiseTotalUsersArray = [];
        $automateCsvPath = sprintf('%s/%s%s', $this->projectDir, $this->generatedUsersFilePath, date('Y-m-d'));
        $this->createDirectoryIfNotExist($automateCsvPath);
        foreach ($userPrefixes as $prefix) {
            $csvPath = sprintf('%s/%s', $automateCsvPath, $prefix);
            $this->createDirectoryIfNotExist($csvPath);

            foreach ($this->lineItemSlugs as $lineSlugs) {
                $slugTotalUsersCreated = 0;
                $csvFilename = sprintf('%s-%s.csv', $lineSlugs, $prefix);
                $csvData = $assignmentCsvData = [];
                for ($batchIncrementValue = 1; $batchIncrementValue <= $batchSize; $batchIncrementValue++) {
                    $username = sprintf(
                        '%s_%s_%d',
                        $lineSlugs,
                        $prefix,
                        (
                            (int)$userNameLastIndexes[$lineSlugs] + $batchIncrementValue
                        )
                    );
                    $userPassword = $this->createUserPassword();
                    $userGroupId = $groupPrefix
                        ? $this->getUserGroupId($userGroupIds, $userGroupAssignCount)
                        : '';

                    $csvData[] = [$username, $userPassword,$userGroupId];
                    $assignmentCsvData[] = [$username, $lineSlugs];

                    $slugTotalUsersCreated++;
                }
                $this->writeUserAssignmentCsvData(
                    $csvPath,
                    $lineSlugs,
                    $prefix,
                    $csvFilename,
                    $csvData,
                    $assignmentCsvData
                );
                $this->writeUserAssignmentAggregratedCsvData(
                    $automateCsvPath,
                    $csvData,
                    $assignmentCsvData
                );
                $slugWiseTotalUsersArray[$lineSlugs] = array_key_exists($lineSlugs, $slugWiseTotalUsersArray)
                    ? $slugWiseTotalUsersArray[$lineSlugs] +  $slugTotalUsersCreated
                    : $slugTotalUsersCreated;
            }
        }

        return $slugWiseTotalUsersArray;
    }

    private function createUserPassword(): string
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstvwxyz'), 0, 8);
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
                $userNameArray = explode('_', (string) $userData);
                $userNameLastIndex = self::DEFAULT_USERNAME_INCREMENT_VALUE;
                $index = end($userNameArray);
                if (count($userNameArray) > 0 && is_numeric($index)) {
                    $userNameLastIndex = (int) $index;
                }
                $userNameIncrementArray[$slug] = $userNameLastIndex;
            }
        }

        return $userNameIncrementArray;
    }

    private function createDirectoryIfNotExist(string $path): void
    {
        if (!$this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path);
        }
    }

    private function getUserGroupId(array $userGroupIds, int $userGroupAssignCount): string
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

    private function writeUserAssignmentCsvData(
        string $csvPath,
        string $lineSlugs,
        string $prefix,
        string $csvFilename,
        array $csvData,
        array $assignmentCsvData
    ): void {

        $this->csvWriter->writeCsv(
            sprintf('%s/%s', $csvPath, $csvFilename),
            self::USER_CSV_HEAD,
            $csvData
        );
        $this->csvWriter->writeCsv(
            sprintf('%s/Assignments-%s-%s.csv', $csvPath, $lineSlugs, $prefix),
            self::ASSIGNMENT_CSV_HEAD,
            $assignmentCsvData
        );
    }

    private function writeUserAssignmentAggregratedCsvData(
        string $automateCsvPath,
        array $csvData,
        array $assignmentCsvData
    ): void {

        $this->csvWriter->writeCsv(
            sprintf('%s/users_aggregated.csv', $automateCsvPath),
            self::USER_CSV_HEAD,
            $csvData
        );
        $this->csvWriter->writeCsv(
            sprintf('%s/assignments_aggregated.csv', $automateCsvPath),
            self::ASSIGNMENT_CSV_HEAD,
            $assignmentCsvData
        );
    }
}
