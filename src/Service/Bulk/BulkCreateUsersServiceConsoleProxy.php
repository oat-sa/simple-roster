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
use OAT\SimpleRoster\DataTransferObject\UserCreationResult;
use OAT\SimpleRoster\DataTransferObject\UserCreationResultMessage;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Lti\Service\ColumnGroupResolver;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;

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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createUsers(
        array $lineItemIds,
        array $lineItemSlugs,
        CreateUserServiceContext $createUserServiceContext,
        ?string $groupPrefix,
        string $date
    ): UserCreationResult {
        $this->checkLineItemsExists($lineItemIds, $lineItemSlugs);

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

        $slugTotalUsers = array_fill_keys(
            array_map(fn($item) => $item->getSlug(), $lineItems),
            $createUserServiceContext->getPrefixesCount()
        );

        $groupResolver = $this->buildGroupResolver(
            $groupPrefix,
            $createUserServiceContext
        );

        $this->service->generate(
            $lineItems,
            $date,
            $createUserServiceContext,
            $groupResolver
        );

        $message = $this->userCreationMessage->normalizeMessage(
            $slugTotalUsers,
            $createUserServiceContext->getPrefixes()
        );

        return new UserCreationResult($message, $notExistLineItemsArray);
    }

    protected function checkLineItemsExists(array $lineItemIds, array $lineItemSlugs): void
    {
        if (empty($lineItemIds) && empty($lineItemSlugs)) {
            $lineItems = $this->lineItemRepository->findAllAsCollection()->jsonSerialize();
            if (empty($lineItems)) {
                throw new LineItemNotFoundException('No line items were found in database.');
            }
        }
    }

    protected function buildGroupResolver(
        ?string $groupPrefix,
        CreateUserServiceContext $createUserServiceContext
    ): ?ColumnGroupResolver {
        $resolver = null;
        if ($groupPrefix) {
            $userGroupIds = $this->generateGroupIdsService->generateGroupIds(
                $groupPrefix,
                $this->ltiInstanceRepository->findAllAsCollection()
            );
            $userGroupAssignCount = (int)ceil(
                $createUserServiceContext->getPrefixesCount() / count($userGroupIds)
            );
            $resolver = new ColumnGroupResolver($userGroupIds, $userGroupAssignCount);
        }

        return $resolver;
    }
}
