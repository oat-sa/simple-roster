<?php

namespace OAT\SimpleRoster\Tests\Unit\EventSubscriber;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Events\LtiInstanceUpdatedEvent;
use OAT\SimpleRoster\EventSubscriber\LtiCacheInvalidatorSubscriber;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

class LtiCacheInvalidatorSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([
            LtiInstanceUpdatedEvent::NAME => ['onLtiInstanceUpdated', 10],
        ], LtiCacheInvalidatorSubscriber::getSubscribedEvents());
    }

    public function testLogErrorOnInvalidCacheDriver(): void
    {
        $configurationMock = self::createMock(Configuration::class);
        $configurationMock->method('getResultCache')->willReturn(null);

        $entityManagerMock = self::createMock(EntityManagerInterface::class);
        $entityManagerMock->method('getConfiguration')->willReturn($configurationMock);

        $ltiInstanceRepositoryMock = self::createMock(LtiInstanceRepository::class);
        $ltiInstanceRepositoryMock->expects(self::never())->method('findAllAsCollection');

        $subscriber = new LtiCacheInvalidatorSubscriber(
            $ltiInstanceRepositoryMock,
            $entityManagerMock,
            self::createMock(LoggerInterface::class)
        );

        $subscriber->onLtiInstanceUpdated();
    }

    public function testCachePoolExceptionHandling(): void
    {
        $cachePoolMock = self::createMock(CacheItemPoolInterface::class);
        $cachePoolMock
            ->method('deleteItem')
            ->willThrowException(new InvalidArgumentException("test exception message"));

        $configurationMock = self::createMock(Configuration::class);
        $configurationMock->method('getResultCache')->willReturn($cachePoolMock);

        $entityManagerMock = self::createMock(EntityManagerInterface::class);
        $entityManagerMock->method('getConfiguration')->willReturn($configurationMock);

        $ltiInstanceRepositoryMock = self::createMock(LtiInstanceRepository::class);
        $ltiInstanceRepositoryMock->expects(self::never())->method('findAllAsCollection');

        $subscriber = new LtiCacheInvalidatorSubscriber(
            $ltiInstanceRepositoryMock,
            $entityManagerMock,
            self::createMock(LoggerInterface::class)
        );

        $subscriber->onLtiInstanceUpdated();
    }
}
