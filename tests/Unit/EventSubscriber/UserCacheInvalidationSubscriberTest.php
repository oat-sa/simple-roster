<?php declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\Assignment;
use App\Entity\User;
use App\EventSubscriber\UserCacheInvalidationSubscriber;
use App\Generator\UserCacheIdGenerator;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserCacheInvalidationSubscriberTest extends TestCase
{
    /** @var UserCacheInvalidationSubscriber */
    private $subject;

    /** @var EntityManager|MockObject */
    private $entityManager;

    /** @var UnitOfWork|MockObject */
    private $unitOfWork;

    /** @var UserCacheIdGenerator|MockObject */
    private $userCacheIdGenerator;

    /** @var Cache|MockObject */
    private $resultCacheImplementation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->userCacheIdGenerator = $this->createMock(UserCacheIdGenerator::class);
        $this->resultCacheImplementation = $this->createMock(Cache::class);

        $doctrineConfiguration = $this->createMock(Configuration::class);
        $doctrineConfiguration
            ->method('getResultCacheImpl')
            ->willReturn($this->resultCacheImplementation);

        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->entityManager
            ->method('getConfiguration')
            ->willReturn($doctrineConfiguration);

        $this->subject = new UserCacheInvalidationSubscriber($this->userCacheIdGenerator);
    }

    public function testSubscribedEvents(): void
    {
        $this->assertEquals([Events::onFlush], $this->subject->getSubscribedEvents());
    }

    public function testItInvalidatesSingleUserCacheUponEntityInsertion(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $this->setUnitOfWorkExpectations([$user]);

        $this->assertCacheDeletion([$user->getUsername()]);
    }

    public function testItInvalidatesSingleUserCacheUponEntityUpdate(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $this->setUnitOfWorkExpectations([], [$user]);

        $this->assertCacheDeletion([$user->getUsername()]);
    }

    public function testItInvalidatesSingleUserCacheUponEntityDeletion(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $this->setUnitOfWorkExpectations([], [], [$user]);

        $this->assertCacheDeletion([$user->getUsername()]);
    }

    public function testItInvalidatesMultipleUsersCacheUponEntityInsertion(): void
    {
        $user1 = (new User())->setUsername('expectedUsername1');
        $user2 = (new User())->setUsername('expectedUsername2');

        $this->setUnitOfWorkExpectations([], [], [$user1, $user2]);

        $this->assertCacheDeletion([$user1->getUsername(), $user2->getUsername()]);
    }

    public function testItInvalidatesMultipleAssignmentsCacheUponEntityUpdate(): void
    {
        $user = (new User())->setUsername('expectedUsername');
        $assignment1 = (new Assignment())->setUser($user);
        $assignment2 = (new Assignment())->setUser($user);

        $this->setUnitOfWorkExpectations([], [$assignment1, $assignment2]);

        // TODO: Cache gets cleared for each updated entity, this could be improved
        $this->assertCacheDeletion([$user->getUsername(), $user->getUsername()]);
    }

    private function assertCacheDeletion(array $expectedUsernames): void
    {
        $expectedCacheIds = array_map(
            static function ($expectedUsername) {
                return sprintf('%s.cacheId', $expectedUsername);
            },
            $expectedUsernames
        );

        $this->userCacheIdGenerator
            ->expects($this->exactly(count($expectedUsernames)))
            ->method('generate')
            ->withConsecutive(
                ...array_map(
                    static function ($expectedUsername) {
                        return [$expectedUsername];
                    },
                    $expectedUsernames
                )
            )
            ->willReturnOnConsecutiveCalls(...$expectedCacheIds);

        $eventArgs = new OnFlushEventArgs($this->entityManager);

        $this->resultCacheImplementation
            ->expects($this->exactly(count($expectedUsernames)))
            ->method('delete')
            ->withConsecutive(
                ...array_map(
                    static function ($expectedCacheId) {
                        return [$expectedCacheId];
                    },
                    $expectedCacheIds
                )
            );

        $this->subject->onFlush($eventArgs);
    }

    private function setUnitOfWorkExpectations(
        array $insertedEntities = [],
        array $updatedEntities = [],
        array $deletedEntities = []
    ): void {
        $this->unitOfWork
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertedEntities);

        $this->unitOfWork
            ->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updatedEntities);

        $this->unitOfWork
            ->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletedEntities);
    }
}
