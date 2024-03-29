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

use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Events\LtiInstanceUpdatedEvent;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LtiCacheInvalidatorSubscriber implements EventSubscriberInterface
{
    private LtiInstanceRepository $ltiInstanceRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        LtiInstanceRepository $ltiInstanceRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->ltiInstanceRepository = $ltiInstanceRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LtiInstanceUpdatedEvent::NAME => ['onLtiInstanceUpdated', 10],
        ];
    }

    public function onLtiInstanceUpdated(): void
    {
        $this->logger->info('Got LtiInstanceUpdated event. Try to update cache.');

        $cache = $this->entityManager->getConfiguration()->getResultCache();

        if (null === $cache) {
            $this->logger->error('Cannot get cache driver from doctrine config. Abort cache updating.');
            return;
        }

        /** @psalm-suppress InvalidCatch */
        try {
            $cache->deleteItem(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES);
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                sprintf('Delete cache error - [%s].  Abort cache updating.', $e->getMessage()),
                ['trace' => $e->getTraceAsString()]
            );
            return;
        }

        //warmup by getting all from db
        $this->ltiInstanceRepository->findAllAsCollection();

        $this->logger->info('LtiInstance cache successfully updated.');
    }
}
