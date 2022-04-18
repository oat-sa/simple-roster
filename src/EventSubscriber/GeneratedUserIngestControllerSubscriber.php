<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\EventSubscriber;

use OAT\SimpleRoster\Events\LineItemUpdated;
use OAT\SimpleRoster\Lti\Service\ColumnGroupResolver;
use OAT\SimpleRoster\Lti\Service\GenerateGroupIdsService;
use OAT\SimpleRoster\Lti\Service\UserGenerator\ParametersBag;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\Service\Bulk\CreateUserServiceContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeneratedUserIngestControllerSubscriber implements EventSubscriberInterface
{
    public const NAME = 'line-item.updated';

    private bool $enabled;

    private LoggerInterface $logger;
    private BulkCreateUsersService $createUsersService;
    private GenerateGroupIdsService $generateGroupIdsService;
    private LtiInstanceRepository $ltiInstanceRepository;
    private LineItemRepository $lineItemRepository;
    private FolderSyncService $userFolderSync;

    private CreateUserServiceContext $createUserServiceContext;
    private ParametersBag $parametersBag;

    public function __construct(
        LoggerInterface $logger,
        BulkCreateUsersService $createService,
        GenerateGroupIdsService $generateGroupIdsService,
        LtiInstanceRepository $ltiInstanceRepository,
        LineItemRepository $lineItemRepository,
        FolderSyncService $userFolderSync,
        CreateUserServiceContext $createUserServiceContext,
        ParametersBag $parametersBag,
        bool $enabled
    ) {
        $this->logger = $logger;
        $this->enabled = $enabled;
        $this->createUsersService = $createService;
        $this->generateGroupIdsService = $generateGroupIdsService;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->lineItemRepository = $lineItemRepository;
        $this->userFolderSync = $userFolderSync;
        $this->createUserServiceContext = $createUserServiceContext;
        $this->parametersBag = $parametersBag;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LineItemUpdated::NAME => ['onLineItemUpdated', 10],
        ];
    }

    public function onLineItemUpdated(LineItemUpdated $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logger->info('Got LineItemUpdate event', [
            'line_items_slugs' => $event->getLineItemSlugs(),
        ]);

        $ltiCollection = $this->ltiInstanceRepository->findAllAsCollection();

        $lineItems = $this->lineItemRepository->findLineItemsByCriteria(
            (new FindLineItemCriteria())->addLineItemSlugs(...$event->getLineItemSlugs())
        )->jsonSerialize();

        $groupPrefix = $this->parametersBag->getGroupPrefix();

        $groupResolver = empty($groupPrefix) ? null : new ColumnGroupResolver(
            $this->generateGroupIdsService->generateGroupIds($groupPrefix, $ltiCollection),
            $this->createUserServiceContext->getPrefixesCount()
        );

        $date = date('Y-m-d');

        $this->createUsersService->generate(
            $lineItems,
            $date,
            $this->createUserServiceContext,
            $groupResolver
        );

        $this->userFolderSync->sync($date);
    }
}
