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

namespace OAT\SimpleRoster\Tests\Unit\MessageHandler;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use OAT\SimpleRoster\Exception\CacheWarmupException;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\MessageHandler\WarmUpGroupedUserCacheMessageHandler;
use OAT\SimpleRoster\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class WarmUpGroupedUserCacheMessageHandlerTest extends TestCase
{
    private WarmUpGroupedUserCacheMessageHandler $subject;
    private EntityManagerInterface&MockObject $entityManager;
    private UserRepository&MockObject $userRepository;
    private UserCacheIdGenerator&MockObject $cacheIdGenerator;
    private CacheItemPoolInterface&MockObject $resultCacheImplementation;
    private LoggerInterface&MockObject $messengerLogger;
    private LoggerInterface&MockObject $cacheWarmupLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->cacheIdGenerator = $this->createMock(UserCacheIdGenerator::class);

        $this->resultCacheImplementation = $this->createMock(CacheItemPoolInterface::class);
        $ormConfiguration = $this->createMock(Configuration::class);
        $ormConfiguration
            ->method('getResultCache')
            ->willReturn($this->resultCacheImplementation);

        $this->entityManager
            ->method('getConfiguration')
            ->willReturn($ormConfiguration);

        $this->messengerLogger = $this->createMock(LoggerInterface::class);
        $this->cacheWarmupLogger = $this->createMock(LoggerInterface::class);

        $this->subject = new WarmUpGroupedUserCacheMessageHandler(
            $this->entityManager,
            $this->userRepository,
            $this->cacheIdGenerator,
            $this->messengerLogger,
            $this->cacheWarmupLogger,
            7200
        );
    }

    public function testItIsInvokableAndTaggedAsMessageHandler(): void
    {
        self::assertTrue(is_callable($this->subject), 'Handler should be callable (__invoke).');

        $ref = new ReflectionClass($this->subject);
        self::assertTrue($ref->hasMethod('__invoke'));

        $attrs = $ref->getAttributes(AsMessageHandler::class);
        self::assertNotEmpty($attrs, 'Handler should have #[AsMessageHandler] attribute.');
    }

    public function testItThrowsExceptionIfResultCacheIsNotConfigured(): void
    {
        $this->expectException(DoctrineResultCacheImplementationNotFoundException::class);
        $this->expectExceptionMessage('Doctrine result cache implementation is not configured.');

        $ormConfiguration = $this->createMock(Configuration::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager
            ->method('getConfiguration')
            ->willReturn($ormConfiguration);

        new WarmUpGroupedUserCacheMessageHandler(
            $entityManager,
            $this->userRepository,
            $this->cacheIdGenerator,
            $this->messengerLogger,
            $this->cacheWarmupLogger,
            7200
        );
    }

    public function testItLogsAndBubblesUpUnexpectedException(): void
    {
        $expectedExceptionMessage = "Unsuccessful cache warmup for user 'testUser'. Error: Ooops...";

        $this->cacheIdGenerator
            ->method('generate')
            ->willThrowException(new Exception('Ooops...'));

        $this->messengerLogger
            ->expects(self::once())
            ->method('error')
            ->with($expectedExceptionMessage);

        $this->cacheWarmupLogger
            ->expects(self::once())
            ->method('error')
            ->with($expectedExceptionMessage);

        $message = new WarmUpGroupedUserCacheMessage(['testUser']);

        try {
            $this->subject->__invoke($message);
            self::fail('Expected exception was not thrown');
        } catch (Exception $exception) {
            // Do nothing
        }
    }

    public function testItThrowsExceptionIfResultCacheDoesNotContainKeyAfterWarmup(): void
    {
        $this->expectException(CacheWarmupException::class);
        $this->expectExceptionMessage("Result cache does not contain key 'testCacheId' after warmup.");

        $this->cacheIdGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('testUsername')
            ->willReturn('testCacheId');

        $this->resultCacheImplementation
            ->expects(self::once())
            ->method('deleteItem')
            ->with('testCacheId');

        $this->resultCacheImplementation
            ->expects(self::once())
            ->method('hasItem')
            ->willReturn(false);

        $message = new WarmUpGroupedUserCacheMessage(['testUsername']);

        $this->subject->__invoke($message);
    }

    public function testItLogsAndWarmsUpUserCache(): void
    {
        $this->cacheIdGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('testUsername')
            ->willReturn('testCacheId');

        $this->resultCacheImplementation
            ->expects(self::once())
            ->method('deleteItem')
            ->with('testCacheId');

        $this->resultCacheImplementation
            ->expects(self::once())
            ->method('hasItem')
            ->willReturn(true);

        $this->userRepository
            ->expects(self::once())
            ->method('findByUsernameWithAssignments')
            ->with('testUsername');

        $expectedLogMessage = "Result cache for user 'testUsername' was successfully warmed up";
        $expectedLogContext = [
            'cacheKey' => 'testCacheId',
            'cacheTtl' => 7200,
        ];

        $this->messengerLogger
            ->expects(self::once())
            ->method('info')
            ->with($expectedLogMessage, $expectedLogContext);

        $this->cacheWarmupLogger
            ->expects(self::once())
            ->method('info')
            ->with($expectedLogMessage, $expectedLogContext);

        $message = new WarmUpGroupedUserCacheMessage(['testUsername']);

        $this->subject->__invoke($message);
    }
}
