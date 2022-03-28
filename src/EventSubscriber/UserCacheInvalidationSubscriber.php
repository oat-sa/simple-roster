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

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserCacheInvalidationSubscriber implements EventSubscriber
{
    /** @var UserCacheWarmerService */
    private UserCacheWarmerService $userCacheWarmerService;

    /** @var UserCacheIdGenerator */
    private UserCacheIdGenerator $userCacheIdGenerator;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var User[] */
    private array $usersToInvalidate = [];

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
     * @throws InvalidArgumentException
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        $resultCache = $entityManager->getConfiguration()->getResultCache();

        if (!$resultCache instanceof CacheItemPoolInterface) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        foreach ($this->usersToInvalidate as $user) {
            $this->clearUserCache($user, $resultCache);
        }
    }

    /**
     * @throws Throwable
     * @throws InvalidArgumentException
     */
    private function clearUserCache(User $user, CacheItemPoolInterface $resultCacheImplementation): void
    {
        $username = (string)$user->getUsername();
        $cacheKey = $this->userCacheIdGenerator->generate($username);
        $resultCacheImplementation->deleteItem($cacheKey);

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
