<?php

/*
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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonException;
use LogicException;
use Monolog\Logger;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserCacheInvalidationSubscriberTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var TransportInterface */
    private $cacheWarmupTransport;

    /** @var CacheItemPoolInterface */
    private $resultCache;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();
        $this->setUpTestLogHandler('cache_warmup');

        $this->cacheWarmupTransport = self::getContainer()->get('messenger.transport.cache-warmup');
        $this->userCacheIdGenerator = self::getContainer()->get(UserCacheIdGenerator::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCache();

        if (!$resultCacheImplementation instanceof CacheItemPoolInterface) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->resultCache = $resultCacheImplementation;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws InvalidArgumentException
     * @throws NonUniqueResultException
     * @throws EntityNotFoundException
     * @throws JsonException
     */
    public function testItInvalidatesCacheUponUserEntityUpdate(): void
    {
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $username = 'user1';
        $cacheId = $this->userCacheIdGenerator->generate($username);

        // Trigger cache by query
        $user = $this->userRepository->findByUsernameWithAssignments($username);
        self::assertTrue($this->resultCache->hasItem($cacheId));

        $user->setGroupId('letsChangeIt');

        $this->userRepository->flush();

        $this->assertCacheInvalidation($username);
    }

    /**
     * @throws OptimisticLockException
     * @throws InvalidArgumentException
     * @throws ORMException
     * @throws NonUniqueResultException
     * @throws JsonException
     * @throws EntityNotFoundException
     */
    public function testItInvalidatesCacheUponAssignmentEntityUpdate(): void
    {
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $username = 'user1';
        $cacheId = $this->userCacheIdGenerator->generate($username);

        // Trigger cache by query
        $user = $this->userRepository->findByUsernameWithAssignments($username);
        self::assertTrue($this->resultCache->hasItem($cacheId));

        $user->getLastAssignment()->setState(Assignment::STATE_COMPLETED);

        $this->userRepository->flush();

        $this->assertCacheInvalidation($username);
    }

    /**
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    private function assertCacheInvalidation(string $username): void
    {
        $cacheId = $this->userCacheIdGenerator->generate($username);
        self::assertFalse($this->resultCache->hasItem($cacheId));

        /** @var Envelope[] $queueMessages */
        $queueMessages = $this->cacheWarmupTransport->get();
        self::assertCount(1, $queueMessages);

        $message = $queueMessages[0]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);
        self::assertSame([$username], $message->getUsernames());

        $this->assertHasLogRecord([
            'message' => sprintf("Cache for user '%s' was successfully invalidated.", $username),
            'context' => [
                'cacheKey' => $cacheId,
            ],
        ], Logger::INFO);

        $this->assertHasLogRecord([
            'message' => sprintf("Cache warmup event was successfully dispatched for users '%s'", $username),
            'context' => [],
        ], Logger::INFO);
    }
}
