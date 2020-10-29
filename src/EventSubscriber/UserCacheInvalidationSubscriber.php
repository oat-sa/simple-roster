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

namespace App\EventSubscriber;

use App\Entity\Assignment;
use App\Entity\User;
use App\Exception\DoctrineResultCacheImplementationNotFoundException;
use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class UserCacheInvalidationSubscriber implements EventSubscriber
{
    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    public function __construct(UserCacheIdGenerator $userCacheIdGenerator)
    {
        $this->userCacheIdGenerator = $userCacheIdGenerator;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
        ];
    }

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $scheduledEntityChanges = [
            'insert' => $unitOfWork->getScheduledEntityInsertions(),
            'update' => $unitOfWork->getScheduledEntityUpdates(),
            'delete' => $unitOfWork->getScheduledEntityDeletions(),
        ];
        foreach ($scheduledEntityChanges as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof User) {
                    $this->clearUserCache($entity, $entityManager);
                } elseif ($entity instanceof Assignment) {
                    $this->clearUserCache($entity->getUser(), $entityManager);
                }
            }
        }
    }

    /**
     * @throws DoctrineResultCacheImplementationNotFoundException
     */
    private function clearUserCache(User $user, EntityManager $entityManager): void
    {
        $resultCacheImplementation = $entityManager
            ->getConfiguration()
            ->getResultCacheImpl();

        if ($resultCacheImplementation === null) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $resultCacheImplementation->delete($this->userCacheIdGenerator->generate((string)$user->getUsername()));
        $this->warmUserCache($user, $entityManager);
    }

    private function warmUserCache(User $user, EntityManager $entityManager): void
    {
        if ($entityManager->getUnitOfWork()->isInIdentityMap($user)) {
            // Repository must be instantiated here because lazy loading is not available for doctrine event
            // subscribers. Injecting it in constructor leads to circular reference in DI container.
            /** @var UserRepository $userRepository * */
            $userRepository = $entityManager->getRepository(User::class);

            // Refresh by query
            $userRepository->findByUsernameWithAssignments((string)$user->getUsername());
        }
    }
}
