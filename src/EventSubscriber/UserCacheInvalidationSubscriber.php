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

namespace OAT\SimpleRoster\EventSubscriber;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use Psr\Log\LoggerInterface;
use Throwable;

class UserCacheInvalidationSubscriber implements EventSubscriber
{
    /** @var UserCacheWarmerService */
    private $userCacheWarmerService;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var LoggerInterface */
    private $logger;

    /** @var User[] */
    private $usersToInvalidate = [];

    public function __construct(
        UserCacheWarmerService $userCacheWarmerService,
        UserCacheIdGenerator $userCacheIdGenerator,
        LoggerInterface $cacheWarmupLogger
    ) {
        $this->userCacheWarmerService = $userCacheWarmerService;
        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->logger = $cacheWarmupLogger;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    /**
     * @throws Throwable
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $this->usersToInvalidate[$entity->getUsername()] = $entity;
            } elseif ($entity instanceof Assignment) {
                $this->usersToInvalidate[$entity->getUser()->getUsername()] = $entity->getUser();
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        /** @var CacheProvider $resultCacheImplementation */
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        foreach ($this->usersToInvalidate as $user) {
            $this->clearUserCache($user, $resultCacheImplementation);
        }
    }

    /**
     * @throws Throwable
     */
    private function clearUserCache(User $user, CacheProvider $resultCacheImplementation): void
    {
        $username = (string)$user->getUsername();
        $cacheKey = $this->userCacheIdGenerator->generate($username);
        $resultCacheImplementation->delete($cacheKey);

        $this->logger->info(
            sprintf(
                "Cache for user '%s' was successfully invalidated.",
                $username
            ),
            [
                'cacheKey' => $cacheKey,
            ]
        );

        $this->userCacheWarmerService->process((new UsernameCollection())->add($username));
    }
}
