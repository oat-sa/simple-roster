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

namespace App\Tests\Functional\Command\Cache;

use App\Command\Cache\DoctrineResultCacheWarmerCommand;
use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use App\Repository\UserRepository;
use App\Repository\LtiInstanceRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use App\Tests\Traits\LoggerTestingTrait;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use InvalidArgumentException;
use LogicException;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DoctrineResultCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var Configuration */
    private $ormConfiguration;

    /** @var Cache */
    private $resultCacheImplementation;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(DoctrineResultCacheWarmerCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->ormConfiguration = $entityManager->getConfiguration();
        $resultCacheImplementation = $this->ormConfiguration->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof Cache) {
            throw new LogicException('Doctrine result cache is not configured.');
        }
        $this->resultCacheImplementation = $resultCacheImplementation;

        $this->userCacheIdGenerator = self::$container->get(UserCacheIdGenerator::class);

        $this->setUpDatabase();
        $this->setUpTestLogHandler('cache_warmup');
        $this->loadFixtureByFilename('100usersWithAssignments.yml');
    }

    public function testItThrowsExceptionIfInvalidCachePoolArgumentGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cache pool received.');

        $this->commandTester->execute(
            [
                'cache-pool' => 'unexpected',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function testItWarnsIfThereAreNoLtiInstancesFoundInDatabase(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'lti-instance',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertStringContainsString(
            '[WARNING] There are no LTI instances found in the database.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItCanWarmLtiInstanceResultCache(): void
    {
        $this->loadFixtureByFilename('5ltiInstances.yml');

        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'lti-instance',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertTrue($this->resultCacheImplementation->contains(LtiInstanceRepository::CACHE_ID_ALL_LTI_INSTANCES));

        self::assertStringContainsString(
            '[OK] Result cache entry for 5 LTI instances has been successfully warmed up. [TTL: 3,600 seconds]',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );
    }

    public function testItLogsIfUserCacheWarmupWasUnsuccessful(): void
    {
        $this->ormConfiguration->setResultCacheImpl(new VoidCache());

        self::assertEquals(0, $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => '1',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $expectedLogMessage = sprintf(
                "Unsuccessful cache warmup for user '%s' (cache id: '%s')",
                $username,
                $this->userCacheIdGenerator->generate($username)
            );

            $this->assertHasLogRecordWithMessage($expectedLogMessage, Logger::ERROR);
        }
    }

    public function testItCanWarmResultCacheForAllUsers(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => '1',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertTrue($this->resultCacheImplementation->contains($userCacheId));
        }

        self::assertStringContainsString(
            '[OK] 100 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItCanRefreshAlreadyExistingResultCache(): void
    {
        $this->getEntityManager()
            ->createNativeQuery("UPDATE line_items SET label = 'expected label'", new ResultSetMapping())
            ->execute();

        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => '1',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            /** @var User $user */
            $user = $this->getRepository(User::class)->findOneBy(['username' => $username]);

            self::assertSame('expected label', $user->getLastAssignment()->getLineItem()->getLabel());
        }
    }

    public function testItCanWarmUpResultCacheByListOfUsernames(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => '1',
                '--usernames' => 'user_1,user_2,user_3,user_4,user_5,user_6,user_7,user_8,user_9,user_10',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 10; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertTrue($this->resultCacheImplementation->contains($userCacheId));
        }

        for ($i = 11; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertFalse($this->resultCacheImplementation->contains($userCacheId));
        }

        self::assertStringContainsString(
            '[OK] 10 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItCanWarmUpResultCacheByListOfLineItemSlugs(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => '1',
                '--line-item-slugs' => 'lineItemSlug1,lineItemSlug2',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 60; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertTrue($this->resultCacheImplementation->contains($userCacheId));
        }

        for ($i = 61; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertFalse($this->resultCacheImplementation->contains($userCacheId));
        }

        self::assertStringContainsString(
            '[OK] 60 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItStopsExecutionIfCriteriaDoNotMatchAnyCacheEntries(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--line-item-slugs' => 'invalid',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        self::assertStringContainsString(
            '[OK] No matching cache entries, exiting.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItThrowsExceptionIfBothLineItemIdAndUserIdFiltersAreSpecified(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "'usernames' and 'line-item-slugs' are exclusive options, please specify only one of them"
        );

        $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => '1',
                '--usernames' => 'user_61,user_62,user_63',
                '--line-item-slugs' => 'lineItemSlug1,lineItemSlug2',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function testItThrowsExceptionIfInvalidBatchSizeOptionReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'batch-size' option received.");

        $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--batch-size' => 0,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    /**
     * @dataProvider provideInvalidFilterOption
     */
    public function testItThrowsExceptionIfInvalidUserIdsOptionReceived(string $invalidOptionValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'usernames' option received.");

        $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--usernames' => $invalidOptionValue,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    /**
     * @dataProvider provideInvalidFilterOption
     */
    public function testItThrowsExceptionIfInvalidLineItemIdsOptionReceived(string $invalidOptionValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'line-item-slugs' option received.");

        $this->commandTester->execute(
            [
                'cache-pool' => 'user',
                '--line-item-slugs' => $invalidOptionValue,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    /**
     * @param mixed $modulo
     * @param mixed $remainder
     *
     * #@dataProvider provideInvalidModuloAndRemainderInputs
     */
    public function testItThrowsExceptionIfInvalidModuloOrRemainderParametersReceived(
        string $expectedExceptionMessage,
        $modulo = null,
        $remainder = null
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $input = [
            'cache-pool' => 'user',
        ];

        if (null !== $modulo) {
            $input['--modulo'] = $modulo;
        }

        if (null !== $remainder) {
            $input['--remainder'] = $remainder;
        }

        $this->commandTester->execute(
            $input,
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function testItCanWarmUpUsersInParallelUsingModuloAndRemainderOptions(): void
    {
        self::assertSame(0, $this->commandTester->execute(
            [
                '--modulo' => 6,
                '--remainder' => 0,
                '--batch-size' => 10,
                'cache-pool' => 'user',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertUserCacheIsPartiallyWarmedUpForRemainders(6, 0);

        self::assertSame(0, $this->commandTester->execute(
            [
                '--modulo' => 6,
                '--remainder' => 1,
                '--batch-size' => 10,
                'cache-pool' => 'user',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertUserCacheIsPartiallyWarmedUpForRemainders(6, 0, 1);

        self::assertSame(0, $this->commandTester->execute(
            [
                '--modulo' => 6,
                '--remainder' => 2,
                '--batch-size' => 10,
                'cache-pool' => 'user',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertUserCacheIsPartiallyWarmedUpForRemainders(6, 0, 1, 2);

        self::assertSame(0, $this->commandTester->execute(
            [
                '--modulo' => 6,
                '--remainder' => 3,
                '--batch-size' => 10,
                'cache-pool' => 'user',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertUserCacheIsPartiallyWarmedUpForRemainders(6, 0, 1, 2, 3);

        self::assertSame(0, $this->commandTester->execute(
            [
                '--modulo' => 6,
                '--remainder' => 4,
                '--batch-size' => 10,
                'cache-pool' => 'user',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertUserCacheIsPartiallyWarmedUpForRemainders(6, 0, 1, 2, 3, 4);

        self::assertSame(0, $this->commandTester->execute(
            [
                '--modulo' => 6,
                '--remainder' => 5,
                '--batch-size' => 10,
                'cache-pool' => 'user',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertUserCacheIsPartiallyWarmedUpForRemainders(6, 0, 1, 2, 3, 4, 5);
    }

    private function assertUserCacheIsPartiallyWarmedUpForRemainders(int $modulo, int ...$remainders): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);

        foreach ($userRepository->findAll() as $user) {
            $cacheId = $this->userCacheIdGenerator->generate((string)$user->getUsername());

            in_array($user->getId() % $modulo, $remainders)
                ? self::assertTrue($this->resultCacheImplementation->contains($cacheId))
                : self::assertFalse($this->resultCacheImplementation->contains($cacheId));
        }
    }

    public function provideInvalidFilterOption(): array
    {
        return [
            [','],
            [',,,,,,'],
        ];
    }

    public function provideInvalidModuloAndRemainderInputs(): array
    {
        return [
            'missingRemainderOption' => [
                'expectedExceptionMessage' => "Command option 'remainder' is expected to be specified.",
                'modulo' => 5,
                'remainder' => null,
            ],
            'missingModuloOption' => [
                'expectedExceptionMessage' => "Command option 'modulo' is expected to be specified.",
                'modulo' => null,
                'remainder' => 2,
            ],
            'nonNumericModuloOption' => [
                'expectedExceptionMessage' => "Command option 'modulo' is expected to be numeric.",
                'modulo' => 'invalid',
                'remainder' => 2,
            ],
            'nonNumericRemainderOption' => [
                'expectedExceptionMessage' => "Command option 'remainder' is expected to be numeric.",
                'modulo' => 5,
                'remainder' => 'invalid',
            ],
            'tooLowModuloOption' => [
                'expectedExceptionMessage' => "Invalid 'modulo' option received: 1, expected value: 2 <= m <= 100",
                'modulo' => 1,
                'remainder' => 1,
            ],
            'tooHighModuloOption' => [
                'expectedExceptionMessage' => "Invalid 'modulo' option received: 101, expected value: 2 <= m <= 100",
                'modulo' => 101,
                'remainder' => 1,
            ],
            'tooLowRemainderOption' => [
                'expectedExceptionMessage' => "Invalid 'remainder' option received: -1, expected value: 0 <= r <= 4",
                'modulo' => 5,
                'remainder' => -1,
            ],
            'tooHighRemainderOption' => [
                'expectedExceptionMessage' => "Invalid 'remainder' option received: 5, expected value: 0 <= r <= 4",
                'modulo' => 5,
                'remainder' => 5,
            ],
        ];
    }

    /**
     * Without this tests asserting the command display are failing with plain phpunit (so NOT with bin/phpunit)
     * due to new line/tab characters. This modification does NOT affect bin/phpunit usage.
     */
    private function normalizeDisplay(string $commandDisplay): string
    {
        return trim((string)preg_replace('/\s+/', ' ', $commandDisplay));
    }
}
