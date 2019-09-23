<?php

declare(strict_types=1);

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

namespace App\EventSubscriber;

use App\Entity\Assignment;
use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
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

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $scheduledEntityChanges = [
            'insert' => $unitOfWork->getScheduledEntityInsertions(),
            'update' => $unitOfWork->getScheduledEntityUpdates(),
            'delete' => $unitOfWork->getScheduledEntityDeletions()
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

    private function clearUserCache(User $user, EntityManager $entityManager): void
    {
        $resultCache = $entityManager
            ->getConfiguration()
            ->getResultCacheImpl();

        $resultCache->delete($this->userCacheIdGenerator->generate($user->getUsername()));
    }
}
