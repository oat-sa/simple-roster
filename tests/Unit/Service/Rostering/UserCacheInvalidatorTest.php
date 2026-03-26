<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Service\Rostering\UserCacheInvalidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class UserCacheInvalidatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UserCacheIdGenerator&MockObject $cacheIdGenerator;
    private LoggerInterface&MockObject $logger;
    private UserCacheInvalidator $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->cacheIdGenerator = $this->createMock(UserCacheIdGenerator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subject = new UserCacheInvalidator(
            $this->entityManager,
            $this->cacheIdGenerator,
            $this->logger
        );
    }

    public function testInvalidateThrowsExceptionOnEmptyUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be empty when invalidating user cache.');

        $this->subject->invalidate('');
    }

    public function testInvalidateThrowsExceptionWhenResultCacheIsNotConfigured(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration
            ->expects(self::once())
            ->method('getResultCache')
            ->willReturn(null);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);
        $this->expectExceptionMessage('Doctrine result cache implementation is not configured.');

        $this->subject->invalidate('john');
    }

    public function testInvalidateDeletesCacheKeyAndLogsSuccess(): void
    {
        $resultCache = $this->createMock(CacheItemPoolInterface::class);
        $configuration = $this->createMock(Configuration::class);

        $configuration
            ->expects(self::once())
            ->method('getResultCache')
            ->willReturn($resultCache);

        $this->entityManager
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->cacheIdGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('john')
            ->willReturn('user.john');

        $resultCache
            ->expects(self::once())
            ->method('deleteItem')
            ->with('user.john');

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                "User cache for user 'john' was successfully invalidated.",
                ['cacheKey' => 'user.john']
            );

        $this->subject->invalidate('john');
    }
}
