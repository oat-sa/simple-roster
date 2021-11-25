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
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use Symfony\Component\Filesystem\Filesystem;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Csv\CsvWriterBuilder;

class BulkUserCreationService
{
    /** @var int[] */
    private array $lineItemIds = [];

    /** @var string[] */
    private array $lineItemSlugs = [];

    /** @var int */
    private int $userGroupBatchCount = 0;
    private int $groupIndex = 0;

    /** @var array */
    private const DEFAULT_USERNAME_INCREMENT_VALUE = 0;

    private LineItemRepository $lineItemRepository;
    private AssignmentRepository $assignmentRepository;
    private LtiInstanceRepository $ltiInstanceRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private NativeUserRepository $userRepository;
    private AssignmentIngester $assignmentIngester;
    private Filesystem $filesystem;
    private CsvWriterBuilder $csvWriterBuilder;

    public function __construct(
        LineItemRepository $lineItemRepository,
        AssignmentRepository $assignmentRepository,
        LtiInstanceRepository $ltiInstanceRepository,
        UserPasswordHasherInterface $passwordHasher,
        NativeUserRepository $userRepository,
        AssignmentIngester $assignmentIngester,
        Filesystem $filesystem,
        CsvWriterBuilder $csvWriterBuilder
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->assignmentRepository = $assignmentRepository;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->assignmentIngester = $assignmentIngester;
        $this->filesystem = $filesystem;
        $this->csvWriterBuilder = $csvWriterBuilder;
    }

    public function processData(
        array $lineItemIds,
        array $lineItemSlugs,
        array $userPrefix,
        int $batchSize,
        string $groupPrefix
    ): array {
        $notExistLineItemsArray = [];
        if (!empty($lineItemIds) || !empty($lineItemSlugs)) {
            $this->lineItemIds = $lineItemIds;
            $this->lineItemSlugs = $lineItemSlugs;
            $criteria = $this->getFindLineItemCriteria();
            $lineItemsArray = ($this->lineItemRepository->findLineItemsByCriteria($criteria))->jsonSerialize();
            if (empty($lineItemsArray)) {
                return ['message' => $this->lineItemIds ?
                    implode(',', $this->lineItemIds) . ' LineItem Ids not exist in the system' :
                    implode(',', $this->lineItemSlugs) . ' LineItem Slugs not exist in the system',
                    'status' => 2];
            }
            $lineItemSlugArray = $this->generateSlugData($lineItemsArray);
            $notExistLineItemsArray = $this->lineItemIds ?
                array_diff($lineItemIds, array_keys($lineItemSlugArray)) :
                array_diff($lineItemSlugs, array_values($lineItemSlugArray));
        }

        if (empty($lineItemIds) && empty($lineItemSlugs)) {
            $this->getAllLineItemSlugs();
        }

        $userIncrNo = $this->getLastUserAssignedToLineItems($this->lineItemSlugs);

        $userGroupAssignCount = 0;
        $userGroupIds = [];
        if ($groupPrefix) {
            $userGroupIds = $this->getLoadBalanceGroupID($groupPrefix);
            $userGroupAssignCount = ceil(
                count($userPrefix) * count($this->lineItemSlugs) * $batchSize / count($userGroupIds)
            );
        }

        $automateCsvPath = $_ENV['AUTOMATE_USER_LIST_PATH'] . date('Y-m-d');
        $userCsvHead = ['username','password','groupId'];
        $assignmentCsvHead = ['username','lineItemSlug'];
        $noOfUsersCreated = 0;

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
                $csvFilename = sprintf('%s-%s.csv', $lineSlugs, $prefix);
                $csvDt = $assignmentCsvDt = [];
                foreach (range(1, $batchSize) as $inc) {
                    $username = sprintf('%s_%s_%d', $lineSlugs, $prefix, ((int)$userIncrNo[$lineSlugs] + $inc));
                    $userPassword = $this->createUserPassword();
                    $userGroupId = $groupPrefix ?
                        $this->createUserGroupId($userGroupIds, $userGroupAssignCount) :
                        '';
                    $csvDt[] = [$username, $userPassword,$userGroupId];
                    $assignmentCsvDt[] = [$username, $lineSlugs];
                    $userDtoCollection->add($this->createUserDto($username, $userPassword, $userGroupId));
                    $assignmentDtoCollection->add($this->createAssignmentDto($lineKey, $username));
                    $noOfUsersCreated++;
                }
                $this->csvWriterBuilder->writeCsv(sprintf('%s/%s', $csvPath, $csvFilename), $userCsvHead, $csvDt);
                $this->csvWriterBuilder->writeCsv(
                    sprintf('%s/Assignments-%s-%s.csv', $csvPath, $lineSlugs, $prefix),
                    $assignmentCsvHead,
                    $assignmentCsvDt
                );
                $this->csvWriterBuilder->writeCsv(
                    sprintf('%s/users_aggregated.csv', $automateCsvPath),
                    $userCsvHead,
                    $csvDt
                );
                $this->csvWriterBuilder->writeCsv(
                    sprintf('%s/assignments_aggregated.csv', $automateCsvPath),
                    $assignmentCsvHead,
                    $assignmentCsvDt
                );
            }
        }
        if (!$userDtoCollection->isEmpty()) {
            $this->userRepository->insertMultiple($userDtoCollection);
        }
        if (!$assignmentDtoCollection->isEmpty()) {
            $this->assignmentIngester->ingest($assignmentDtoCollection);
        }
        if ($noOfUsersCreated === 0) {
            return ['message' => 'An unexpected error occurred', 'status' => 0];
        }
        return [
            'message' => $noOfUsersCreated . ' Users have been successfully added',
            'notExistLineItemsArray' => $notExistLineItemsArray,
            'status' => 1
        ];
    }

    private function generateSlugData(array $lineData): array
    {
        $lineItemArray = [];
        $lineData = json_decode(json_encode($lineData), true);
        foreach ($lineData as $lineItem) {
            $lineItemArray[$lineItem['id']] = $lineItem['slug'];
        }
        $this->lineItemSlugs = $lineItemArray;

        return $lineItemArray;
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
        $lineItems = $this->lineItemRepository->findAllSlugsAsArray();
        if (empty($lineItems)) {
            throw new LineItemNotFoundException('No line items were found in database.');
        }
        $lineItemArray = [];
        foreach ($lineItems as $litem) {
            $lineItemArray[$litem['id']] = $litem['slug'];
        }
        $this->lineItemSlugs = $lineItemArray;
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
                $userNameArr = explode('_', (string)$userInfo);
                $userNameLastNo = preg_match('/^\d+$/', end($userNameArr)) ?
                    (int)end($userNameArr) :
                    self::DEFAULT_USERNAME_INCREMENT_VALUE;
                $userNameIncArr[$slug] = $userNameLastNo;
            }
        }

        return $userNameIncArr;
    }

    private function getLoadBalanceGroupID(string $groupPrefix): array
    {
        $totalInstance = $this->ltiInstanceRepository->findAllAsCollection()->count();
        $targetId = 1;
        $groupIds = [];
        while ($targetId <= $totalInstance) {
            $random = substr(md5(random_bytes(10)), 0, 10);
            $groupIds[] = sprintf($groupPrefix . '_%s', $random);
            $targetId++;
        }
        return $groupIds;
    }

    private function createUserGroupId(array $userGroupIds, float $userGroupAssignCount): string
    {
        $userGroupId = '';
        if ($userGroupAssignCount !== 0) {
            if ($this->userGroupBatchCount < $userGroupAssignCount) {
                $userGroupId = $userGroupIds[$this->groupIndex];
                $this->userGroupBatchCount++;
            } else {
                $this->userGroupBatchCount = 1;
                $this->groupIndex++;
                $userGroupId = $userGroupIds[$this->groupIndex];
            }
        }
        return $userGroupId;
    }
}
