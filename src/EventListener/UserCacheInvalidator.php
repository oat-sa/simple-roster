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
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        $scheduledEntityChanges = array(
            'insert' => $unitOfWork->getScheduledEntityInsertions(),
            'update' => $unitOfWork->getScheduledEntityUpdates(),
            'delete' => $unitOfWork->getScheduledEntityDeletions()
        );

        foreach ($scheduledEntityChanges as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof User) {
                    $this->clearUserCache($entity, $entityManager);
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
