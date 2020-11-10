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

namespace OAT\SimpleRoster\Tests\Unit\EventSubscriber;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\EventSubscriber\UserCacheInvalidationSubscriber;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Repository\UserRepository;
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

    /** @var UserRepository|MockObject */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->userCacheIdGenerator = $this->createMock(UserCacheIdGenerator::class);
        $this->resultCacheImplementation = $this->createMock(Cache::class);
        $this->userRepository = $this->createMock(UserRepository::class);

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

        $this->entityManager
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $this->subject = new UserCacheInvalidationSubscriber($this->userCacheIdGenerator);
    }

    public function testSubscribedEvents(): void
    {
        self::assertSame([Events::onFlush], $this->subject->getSubscribedEvents());
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

    public function testItThrowsExceptionIfDoctrineResultCacheImplementationIsNotSet(): void
    {
        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);

        $entityManager = $this->createMock(EntityManager::class);

        $entityManager
            ->method('getConfiguration')
            ->willReturn($this->createMock(Configuration::class));

        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $user = (new User())->setUsername('expectedUsername');
        $assignment1 = (new Assignment())->setUser($user);
        $assignment2 = (new Assignment())->setUser($user);

        $this->setUnitOfWorkExpectations([], [$assignment1, $assignment2]);

        $this->subject->onFlush(new OnFlushEventArgs($entityManager));
    }

    public function testItWarmsUpTheCacheAfterInvalidation(): void
    {
        $user = (new User())->setUsername('expectedUsername');

        $this
            ->unitOfWork
            ->expects(self::once())
            ->method('isInIdentityMap')
            ->with($user)
            ->willReturn(true);

        $this->setUnitOfWorkExpectations([], [], [$user]);

        $this
            ->userRepository
            ->expects(self::once())
            ->method('findByUsernameWithAssignments')
            ->with($user->getUsername());

        $this->assertCacheDeletion([$user->getUsername()]);
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
            ->expects(self::exactly(count($expectedUsernames)))
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
            ->expects(self::exactly(count($expectedUsernames)))
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
            ->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertedEntities);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updatedEntities);

        $this->unitOfWork
            ->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletedEntities);
    }
}
