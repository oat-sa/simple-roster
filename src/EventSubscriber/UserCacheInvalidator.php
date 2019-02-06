<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Assignment;
use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

class UserCacheInvalidator implements EventSubscriber
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

        $scheduledEntityChanges = array(
            'insert' => $unitOfWork->getScheduledEntityInsertions(),
            'update' => $unitOfWork->getScheduledEntityUpdates(),
            'delete' => $unitOfWork->getScheduledEntityDeletions()
        );

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
