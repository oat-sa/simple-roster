<?php declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;

class UserCacheInvalidator
{
    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    public function __construct(UserCacheIdGenerator $userCacheIdGenerator)
    {
        $this->userCacheIdGenerator = $userCacheIdGenerator;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        $scheduledEntityChanges = array(
            'insert' => $uow->getScheduledEntityInsertions(),
            'update' => $uow->getScheduledEntityUpdates(),
            'delete' => $uow->getScheduledEntityDeletions()
        );

        foreach ($scheduledEntityChanges as $change => $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof User) {
                    $this->clearUserCache($entity, $em);
                }
            }
        }
    }

    private function clearUserCache(User $user, EntityManager $entityManager): void
    {
        $cacheId = $this->userCacheIdGenerator->generate($user->getUsername());

        $resultCache = $entityManager->getConfiguration()->getResultCacheImpl();

        $resultCache->delete($cacheId);
    }
}
