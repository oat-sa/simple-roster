<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use OAT\SimpleRoster\Service\Rostering\RosteringUserCacheSynchronizer;
use OAT\SimpleRoster\Service\Rostering\UserCacheInvalidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class RosteringUserCacheSynchronizerTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $resultCache;
    private UserCacheWarmerService&MockObject $userCacheWarmerService;
    private LoggerInterface&MockObject $logger;
    private RosteringUserCacheSynchronizer $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resultCache = $this->createMock(CacheItemPoolInterface::class);
        $this->userCacheWarmerService = $this->createMock(UserCacheWarmerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->method('getResultCache')
            ->willReturn($this->resultCache);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getConfiguration')
            ->willReturn($configuration);

        $invalidatorLogger = $this->createMock(LoggerInterface::class);
        $invalidator = new UserCacheInvalidator(
            $entityManager,
            new UserCacheIdGenerator(),
            $invalidatorLogger
        );

        $this->subject = new RosteringUserCacheSynchronizer(
            $invalidator,
            $this->userCacheWarmerService,
            $this->logger
        );
    }

    public function testSynchronizeInvalidatesAllMarkedUsersAndWarmsUpOnlyWarmupUsers(): void
    {
        $this->subject->markForInvalidationOnly('anna');
        $this->subject->markForWarmup('bob');
        $this->subject->markForWarmup('anna');
        $this->subject->markForInvalidationOnly('bob');

        $deletedKeys = [];

        $this->resultCache
            ->expects(self::exactly(2))
            ->method('deleteItem')
            ->willReturnCallback(static function (string $cacheKey) use (&$deletedKeys): bool {
                $deletedKeys[] = $cacheKey;

                return true;
            });

        $this->userCacheWarmerService
            ->expects(self::once())
            ->method('process')
            ->with(self::callback(static function (UsernameCollection $usernames): bool {
                return iterator_to_array($usernames) === ['anna', 'bob'];
            }));

        $this->subject->synchronize();

        self::assertSame(['user.anna', 'user.bob'], $deletedKeys);
    }

    public function testSynchronizeInvalidatesWithoutWarmupWhenOnlyInvalidationIsMarked(): void
    {
        $this->subject->markForInvalidationOnly('john');

        $this->resultCache
            ->expects(self::once())
            ->method('deleteItem')
            ->with('user.john')
            ->willReturn(true);

        $this->userCacheWarmerService
            ->expects(self::never())
            ->method('process');

        $this->subject->synchronize();
    }

    public function testSynchronizeLogsAndContinuesWhenInvalidationFails(): void
    {
        $this->subject->markForInvalidationOnly('');
        $this->subject->markForWarmup('john');

        $this->resultCache
            ->expects(self::once())
            ->method('deleteItem')
            ->with('user.john')
            ->willReturn(true);

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                "Unable to invalidate cache for user '' after rostering import.",
                self::arrayHasKey('exception')
            );

        $this->userCacheWarmerService
            ->expects(self::once())
            ->method('process')
            ->with(self::callback(static function (UsernameCollection $usernames): bool {
                return iterator_to_array($usernames) === ['john'];
            }));

        $this->subject->synchronize();
    }

    public function testSynchronizeLogsWhenWarmupFails(): void
    {
        $this->subject->markForWarmup('john');

        $this->resultCache
            ->expects(self::once())
            ->method('deleteItem')
            ->with('user.john')
            ->willReturn(true);

        $this->userCacheWarmerService
            ->expects(self::once())
            ->method('process')
            ->willThrowException(new RuntimeException('Warmup failed'));

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Unable to warm up cache for 1 users after rostering import.',
                self::callback(static function (array $context): bool {
                    return isset($context['usernames'], $context['exception'])
                        && $context['usernames'] === ['john']
                        && $context['exception'] instanceof RuntimeException;
                })
            );

        $this->subject->synchronize();
    }

    public function testResetClearsAllMarkedUsers(): void
    {
        $this->subject->markForWarmup('john');
        $this->subject->markForInvalidationOnly('mike');
        $this->subject->reset();

        $this->resultCache
            ->expects(self::never())
            ->method('deleteItem');

        $this->userCacheWarmerService
            ->expects(self::never())
            ->method('process');

        $this->subject->synchronize();
    }
}

