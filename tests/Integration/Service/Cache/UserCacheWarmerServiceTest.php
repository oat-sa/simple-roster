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
use Monolog\Logger;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Service\Cache\UserCacheWarmerService;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Log\LoggerInterface;
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

        $this->subject = self::$container->get(UserCacheWarmerService::class);
        $this->cacheWarmupTransport = self::$container->get('messenger.transport.cache-warmup');
    }

    public function testItLogsAndBubblesUpExceptions(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ooops...');

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->method('dispatch')
            ->willThrowException(new Exception('Ooops...'));

        $messengerLogger = $this->createMock(LoggerInterface::class);
        $messengerLogger
            ->expects(self::once())
            ->method('error')
            ->with("Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops...");

        $cacheWarmupLogger = $this->createMock(LoggerInterface::class);
        $cacheWarmupLogger
            ->expects(self::once())
            ->method('error')
            ->with("Unsuccessful cache warmup for user 'testUsername1, testUsername2'. Error: Ooops...");

        $subject = new UserCacheWarmerService($messageBus, $messengerLogger, $cacheWarmupLogger);

        $usernameCollection = (new UsernameCollection())
            ->add('testUsername1')
            ->add('testUsername2');

        $subject->process($usernameCollection);
    }

    public function testItSuccessfullyDispatchesEvents(): void
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
