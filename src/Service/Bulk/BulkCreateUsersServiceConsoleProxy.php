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
use OAT\SimpleRoster\Lti\Service\ColumnGroupResolver;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Lti\Service\StateDrivenUserGenerator;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Lti\Service\AssigmentFactoryInterface;
use OAT\SimpleRoster\Storage\UserGenerator\StorageInterface;
use OAT\SimpleRoster\Entity\LineItem;

class BulkCreateUsersServiceConsoleProxy
{
    private LineItemRepository $lineItemRepository;
    private GenerateGroupIdsService $generateGroupIdsService;
    private LtiInstanceRepository $ltiInstanceRepository;
    private UserCreationResultMessage $userCreationMessage;
    private BulkCreateUsersService $service;

    public function __construct(
        LineItemRepository $lineItemRepository,
        GenerateGroupIdsService $generateGroupIdsService,
        LtiInstanceRepository $ltiInstanceRepository,
        UserCreationResultMessage $userCreationMessage,
        BulkCreateUsersService $service
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->generateGroupIdsService = $generateGroupIdsService;
        $this->userCreationMessage = $userCreationMessage;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->service = $service;
    }

    /**
     * @throws LineItemNotFoundException
     */
    public function createUsers(
        array $lineItemIds,
        array $lineItemSlugs,
        array $userPrefixes,
        int $batchSize,
        ?string $groupPrefix,
        string $date
    ): UserCreationResult {
        $notExistLineItemsArray = [];

        $lineItems = [];
        if (!empty($lineItemIds) || !empty($lineItemSlugs)) {
            $criteria = (new FindLineItemCriteria())
                ->addLineItemIds(...$lineItemIds)
                ->addLineItemSlugs(...$lineItemSlugs);
            $lineItems = $this->lineItemRepository->findLineItemsByCriteria($criteria)->jsonSerialize();

            if (empty($lineItems)) {
                $exceptionMessage = $lineItemIds
                    ? implode(',', $lineItemIds) . ' Line item id(s) not exist in the system'
                    : implode(',', $lineItemSlugs) . ' Line item slug(s) not exist in the system';
                throw new LineItemNotFoundException($exceptionMessage);
            }

            $notExistLineItemsArray = $lineItemIds
                ? array_diff($lineItemIds, array_map(fn($item) => $item->getId(), $lineItems))
                : array_diff($lineItemSlugs, array_map(fn($item) => $item->getSlug(), $lineItems));
        }

        if (empty($lineItemIds) && empty($lineItemSlugs)) {
            $lineItems = $this->lineItemRepository->findAllAsCollection()->jsonSerialize();
            if (empty($lineItems)) {
                throw new LineItemNotFoundException('No line items were found in database.');
            }
        }

        $resolver = null;
        if ($groupPrefix) {
            $userGroupIds = $this->generateGroupIdsService->generateGroupIds(
                $groupPrefix,
                $this->ltiInstanceRepository->findAllAsCollection()
            );
            $userGroupAssignCount = (int)ceil(
                count($userPrefixes) * count($lineItems) * $batchSize / count($userGroupIds)
            );
            $resolver = new ColumnGroupResolver($userGroupIds, $userGroupAssignCount);
        }

        $slugTotalUsers = array_fill_keys(
            array_map(fn($item) => $item->getSlug(), $lineItems),
            $batchSize * count($userPrefixes)
        );

        $this->service->generate(
            $lineItems,
            $userPrefixes,
            $batchSize,
            $date,
            $resolver
        );

        $message = $this->userCreationMessage->normalizeMessage($slugTotalUsers, $userPrefixes);

        return new UserCreationResult($message, $notExistLineItemsArray);
    }
}
