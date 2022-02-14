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
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Service\Bulk\BulkCreateUsersService;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeneratedUserIngestControllerSubscriber implements EventSubscriberInterface
{
    public const NAME = 'line-item.updated';

    private LoggerInterface $logger;
    private BulkCreateUsersService $createService;
    private GenerateGroupIdsService $generateGroupIdsService;
    private LtiInstanceRepository $ltiInstanceRepository;
    private LineItemRepository $lineItemRepository;

    private bool $enabled;

    public function __construct(
        LoggerInterface $logger,
        BulkCreateUsersService $createService,
        GenerateGroupIdsService $generateGroupIdsService,
        LtiInstanceRepository $ltiInstanceRepository,
        LineItemRepository $lineItemRepository,
        bool $enabled
    ) {
        $this->logger = $logger;
        $this->enabled = $enabled;
        $this->createService = $createService;
        $this->generateGroupIdsService = $generateGroupIdsService;
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->lineItemRepository = $lineItemRepository;
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
            'line_items_slugs' => $event->getUpdateLineItemCollection()->map(fn($dto) => $dto->getSlug())
        ]);

        $acceptedSlugs = $event->getUpdateLineItemCollection()
            ->filter(fn($dto) => $dto->getStatus() === UpdateLineItemDto::STATUS_ACCEPTED)
            ->map(fn($dto) => $dto->getSlug());

        $batchSize = 20;
        $ltiCollection = $this->ltiInstanceRepository->findAllAsCollection();

        $this->createService->generate(
            $this->lineItemRepository->findLineItemsByCriteria(
                (new FindLineItemCriteria())->addLineItemSlugs(...$acceptedSlugs)
            )->jsonSerialize(),
            ['QA1', 'QA2'],
            $batchSize,
            new ColumnGroupResolver(
                $this->generateGroupIdsService->generateGroupIds("TestGroup", $ltiCollection),
                $batchSize * 2
            )
        );
    }
}
