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

namespace OAT\SimpleRoster\Tests\Functional\Command\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use OAT\SimpleRoster\Command\Cache\UserCacheWarmerCommand;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

class UserCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var TransportInterface */
    private $cacheWarmupTransport;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(UserCacheWarmerCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $resultCacheImplementation = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new LogicException('Doctrine result cache is not configured.');
        }

        $this->cacheWarmupTransport = self::$container->get('messenger.transport.cache-warmup');

        $this->setUpDatabase();
        $this->setUpTestLogHandler('cache_warmup');
    }

    /**
     * @dataProvider provideInvalidParameters
     */
    public function testItThrowsExceptionForIfInvalidParametersReceived(array $input, string $expectedOutput): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedOutput);

        self::assertSame(0, $this->commandTester->execute(
            $input,
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay());
    }

    public function testItDisplaysErrorInCaseOfUnexpectedException(): void
    {
        $kernel = self::bootKernel();

        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findAllUsernamesByCriteriaPaged')
            ->willThrowException(new LogicException('Yaaay'));

        $userRepository
            ->method('countByCriteria')
            ->willReturn(100);

        self::$container->set('test.user_repository', $userRepository);

        $application = new Application($kernel);
        $commandTester = new CommandTester($application->find(UserCacheWarmerCommand::NAME));

        self::assertSame(1, $commandTester->execute([], ['capture_stderr_separately' => true]));
        self::assertStringContainsString('[ERROR] An unexpected error occurred: Yaaay', $commandTester->getDisplay());
    }

    public function testItDetectsIfThereAreNoUsersIngested(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertStringContainsString(
            '[WARNING] There are no users found in the database.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItSuccessfullyInitiatesCacheWarmup(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        self::assertSame(0, $this->commandTester->execute(
            [],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        /** @var Envelope[] $queueMessages */
        $queueMessages = $this->cacheWarmupTransport->get();
        self::assertCount(1, $queueMessages);

        $message = $queueMessages[0]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);

        $expectedUsernames = array_map(static function (User $user): string {
            return $user->getUsername();
        }, $this->getRepository(User::class)->findAll());

        self::assertSame($expectedUsernames, $message->getUsernames());
        self::assertStringContainsString(
            '[OK] Cache warmup for 100 users was successfully initiated.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItSuccessfullyInitiatesCacheWarmupInBatches(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        self::assertSame(0, $this->commandTester->execute(
            [
                '--batch' => 3,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        /** @var Envelope[] $queueMessages */
        $queueMessages = $this->cacheWarmupTransport->get();
        self::assertCount(34, $queueMessages);

        $processedUsernames = [];
        foreach ($queueMessages as $envelope) {
            self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $envelope->getMessage());
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $processedUsernames = array_merge($processedUsernames, $envelope->getMessage()->getUsernames());
        }

        $expectedUsernames = array_map(static function (User $user): string {
            return $user->getUsername();
        }, $this->getRepository(User::class)->findAll());

        self::assertSame($expectedUsernames, $processedUsernames);
        self::assertStringContainsString(
            '[OK] Cache warmup for 100 users was successfully initiated.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItSuccessfullyInitiatesCacheWarmupWithUsernameOption(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        self::assertSame(0, $this->commandTester->execute(
            [
                '--usernames' => 'user_1,user_20,user_67,user_89',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        /** @var Envelope[] $queueMessages */
        $queueMessages = $this->cacheWarmupTransport->get();
        self::assertCount(1, $queueMessages);

        $message = $queueMessages[0]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);

        $expectedUsernames = ['user_1', 'user_20', 'user_67', 'user_89'];

        self::assertSame($expectedUsernames, $message->getUsernames());
        self::assertStringContainsString(
            '[OK] Cache warmup for 4 users was successfully initiated.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItSuccessfullyInitiatesCacheWarmupWithLineItemSlugsOption(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        self::assertSame(0, $this->commandTester->execute(
            [
                '--line-item-slugs' => 'lineItemSlug1,lineItemSlug3',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        /** @var Envelope[] $queueMessages */
        $queueMessages = $this->cacheWarmupTransport->get();
        self::assertCount(1, $queueMessages);

        $message = $queueMessages[0]->getMessage();
        self::assertInstanceOf(WarmUpGroupedUserCacheMessage::class, $message);

        $expectedUsernames = [];
        for ($i = 1; $i <= 50; $i++) {
            $expectedUsernames[] = sprintf('user_%d', $i);
        }

        for ($i = 61; $i <= 100; $i++) {
            $expectedUsernames[] = sprintf('user_%d', $i);
        }

        self::assertSame($expectedUsernames, $message->getUsernames());
        self::assertStringContainsString(
            '[OK] Cache warmup for 90 users was successfully initiated.',
            $this->commandTester->getDisplay()
        );
    }

    public function provideInvalidParameters(): array
    {
        return [
            'invalidBatchOption' => [
                'input' => [
                    '--batch' => -2,
                ],
                'expectedOutput' => "Invalid 'batch' option received.",
            ],
            'invalidUsernamesOption' => [
                'input' => [
                    '--usernames' => '1,4,5,6',
                ],
                'expectedOutput' => "Invalid 'usernames' option received.",
            ],
            'invalidLineItemSlugsOption' => [
                'input' => [
                    '--line-item-slugs' => '1,4,5,6',
                ],
                'expectedOutput' => "Invalid 'line-item-slugs' option received.",
            ],
            'bothUsernamesAndLineItemSlugsOptionsAreSpecified' => [
                'input' => [
                    '--usernames' => 'user_1,user_2',
                    '--line-item-slugs' => 'slug_1,slug_2',
                ],
                'expectedOutput' => "Option 'usernames' and 'line-item-slugs' are exclusive options.",
            ],
        ];
    }
}
