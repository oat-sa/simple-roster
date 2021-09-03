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

namespace OAT\SimpleRoster\Tests\Integration\Service\Cache;

use Exception;
use InvalidArgumentException;
use Monolog\Logger;
use OAT\SimpleRoster\Exception\CacheWarmupException;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class UserCacheWarmerServiceTest extends KernelTestCase
{
    use LoggerTestingTrait;
    use DatabaseTestingTrait;

    /** @var UserCacheWarmerService */
    private $subject;

    /** @var TransportInterface */
    private $cacheWarmupTransport;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpTestLogHandler('messenger', 'cache_warmup');

        $this->subject = self::getContainer()->get(UserCacheWarmerService::class);
        $this->cacheWarmupTransport = self::getContainer()->get('messenger.transport.cache-warmup');
    }

    public function testItThrowsExceptionIfInvalidBatchSizeReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message payload size must be greater or equal to 1.');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messengerLogger = $this->createMock(LoggerInterface::class);
        $cacheWarmupLogger = $this->createMock(LoggerInterface::class);

        new UserCacheWarmerService($messageBus, $messengerLogger, $cacheWarmupLogger, 0, 1000);
    }

    public function testItThrowsExceptionIfInvalidRetryTimeIntervalReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Retry wait time interval must be greater than equal to 1.');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messengerLogger = $this->createMock(LoggerInterface::class);
        $cacheWarmupLogger = $this->createMock(LoggerInterface::class);

        new UserCacheWarmerService($messageBus, $messengerLogger, $cacheWarmupLogger, 100, 0);
    }

    public function testItRetriesEventDispatchingInCaseOfException(): void
    {
        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new Exception('Ooops... Error 1')),
                $this->throwException(new Exception('Ooops... Error 2')),
                $this->throwException(new Exception('Ooops... Error 3')),
                $this->throwException(new Exception('Ooops... Error 4')),
                new Envelope(new stdClass())
            );

        $messengerLogger = $this->createMock(LoggerInterface::class);
        $messengerLogger
            ->expects($this->exactly(4))
            ->method('error')
            ->withConsecutive(
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 1"],
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 2"],
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 3"],
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 4"]
            );

        $messengerLogger
            ->expects($this->exactly(4))
            ->method('warning')
            ->withConsecutive(
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [1/5]'],
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [2/5]'],
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [3/5]'],
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [4/5]'],
            );

        $cacheWarmupLogger = $this->createMock(LoggerInterface::class);
        $cacheWarmupLogger
            ->expects($this->exactly(4))
            ->method('error')
            ->withConsecutive(
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 1"],
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 2"],
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 3"],
                ["Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops... Error 4"]
            );

        $cacheWarmupLogger
            ->expects($this->exactly(4))
            ->method('warning')
            ->withConsecutive(
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [1/5]'],
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [2/5]'],
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [3/5]'],
                ['Unsuccessful cache warmup attempt. Retrying after 1000 microseconds... [4/5]'],
            );

        $subject = new UserCacheWarmerService($messageBus, $messengerLogger, $cacheWarmupLogger, 100, 1000);

        $usernameCollection = (new UsernameCollection())
            ->add('testUsername1')
            ->add('testUsername2');

        $subject->process($usernameCollection);
    }

    public function testItLogsAndBubblesUpExceptionsInCaseRetriesHaveFailed(): void
    {
        $this->expectException(CacheWarmupException::class);
        $this->expectExceptionMessage(
            'Unsuccessful cache warmup after 5 retry attempts. Last error message: Fatal error...'
        );

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->method('dispatch')
            ->willThrowException(new Exception('Fatal error...'));

        $messengerLogger = $this->createMock(LoggerInterface::class);
        $cacheWarmupLogger = $this->createMock(LoggerInterface::class);

        $subject = new UserCacheWarmerService($messageBus, $messengerLogger, $cacheWarmupLogger, 100, 1000);

        $usernameCollection = (new UsernameCollection())
            ->add('testUsername1')
            ->add('testUsername2');

        $subject->process($usernameCollection);
    }

    public function testItSuccessfullyDispatchesEventsInBatches(): void
    {
        $usernames = new UsernameCollection();
        $firstBatchOfUsernames = [];
        $secondBatchOfUsernames = [];
        $thirdBatchOfUsernames = [];

        for ($i = 1; $i <= 250; $i++) {
            $username = sprintf('user_%d', $i);
            $usernames->add($username);

            if ($i <= 100) {
                $firstBatchOfUsernames[] = $username;
            }

            if ($i > 100 && $i <= 200) {
                $secondBatchOfUsernames[] = $username;
            }

            if ($i > 200) {
                $thirdBatchOfUsernames[] = $username;
            }
        }

        $this->subject->process($usernames);

        /** @var Envelope[] $queueMessages */
        $queueMessages = $this->cacheWarmupTransport->get();
        self::assertCount(3, $queueMessages);

        $message = $queueMessages[0]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);
        self::assertSame($firstBatchOfUsernames, $message->getUsernames());

        $message = $queueMessages[1]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);
        self::assertSame($secondBatchOfUsernames, $message->getUsernames());

        $message = $queueMessages[2]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);
        self::assertSame($thirdBatchOfUsernames, $message->getUsernames());

        foreach ([$firstBatchOfUsernames, $secondBatchOfUsernames, $thirdBatchOfUsernames] as $expectedUsernames) {
            $this->assertHasLogRecord([
                'message' => sprintf(
                    "Cache warmup event was successfully dispatched for users '%s'",
                    implode(', ', $expectedUsernames)
                ),
                'context' => [],
            ], Logger::INFO);
        }
    }
}
