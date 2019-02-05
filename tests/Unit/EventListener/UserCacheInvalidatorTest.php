<?php declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\Entity\User;
use App\EventListener\UserCacheInvalidator;
use App\Generator\UserCacheIdGenerator;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

class UserCacheInvalidatorTest extends TestCase
{
    /** @var UserCacheInvalidator */
    private $subject;

    /** @var EntityManager */
    private $entityManager;

    /** @var UnitOfWork */
    private $unitOfWork;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var Cache */
    private $resultCacheImplementation;

    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)->disableOriginalConstructor()->getMock();
        $this->userCacheIdGenerator = $this->getMockBuilder(UserCacheIdGenerator::class)->getMock();
        $this->resultCacheImplementation = $this->getMockBuilder(Cache::class)->getMock();

        $doctrineConfiguration = $this->getMockBuilder(Configuration::class)->getMock();
        $doctrineConfiguration
            ->expects($this->any())
            ->method('getResultCacheImpl')
            ->willReturn($this->resultCacheImplementation);

        $this->entityManager
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->entityManager
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($doctrineConfiguration);

        $this->subject = new UserCacheInvalidator($this->userCacheIdGenerator);
    }

    public function testItInvalidatesSingleUserCacheUponEntityInsertion(): void
    {
        $user = new User();
        $user->setUsername('expectedUsername');

        $this->setUnitOfWorkExpectations([$user]);

        $this->assertCacheDeletion([$user->getUsername()]);
    }

    public function testItInvalidatesSingleUserCacheUponEntityUpdate(): void
    {
        $user = new User();
        $user->setUsername('expectedUsername');

        $this->setUnitOfWorkExpectations([], [$user]);

        $this->assertCacheDeletion([$user->getUsername()]);
    }

    public function testItInvalidatesSingleUserCacheUponEntityDeletion(): void
    {
        $user = new User();
        $user->setUsername('expectedUsername');

        $this->setUnitOfWorkExpectations([], [], [$user]);

        $this->assertCacheDeletion([$user->getUsername()]);
    }

    public function testItInvalidatesMultipleUsersCacheUponEntityInsertion(): void
    {
        $user1 = new User();
        $user1->setUsername('expectedUsername1');

        $user2 = new User();
        $user2->setUsername('expectedUsername2');

        $this->setUnitOfWorkExpectations([], [], [$user1, $user2]);

        $this->assertCacheDeletion([$user1->getUsername(), $user2->getUsername()]);
    }

    private function assertCacheDeletion(array $expectedUsernames): void
    {
        $expectedCacheIds = array_map(
            function ($expectedUsername) {
                return sprintf('%s.cacheId', $expectedUsername);
            },
            $expectedUsernames
        );

        $this->userCacheIdGenerator
            ->expects($this->exactly(count($expectedUsernames)))
            ->method('generate')
            ->withConsecutive(
                ...array_map(
                    function ($expectedUsername) {
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
                    function ($exectedCacheId) {
                        return [$exectedCacheId];
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
    ) {
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
