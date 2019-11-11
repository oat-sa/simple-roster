<?php

declare(strict_types=1);

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

namespace App\Tests\Functional\Command\Cache;

use App\Command\Cache\DoctrineResultCacheWarmerCommand;
use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DoctrineResultCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var Cache */
    private $doctrineResultCache;

    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(DoctrineResultCacheWarmerCommand::NAME));

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->doctrineResultCache = $entityManager->getConfiguration()->getResultCacheImpl();

        $this->userCacheIdGenerator = self::$container->get(UserCacheIdGenerator::class);

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
    }

    public function testItCanWarmResultCacheForAllUsers(): void
    {
        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => '1',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            $this->assertTrue($this->doctrineResultCache->contains($userCacheId));
        }

        $this->assertStringContainsString(
            '[OK] 100 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItCanRefreshAlreadyExistingResultCache(): void
    {
        $this->getEntityManager()
            ->createNativeQuery("UPDATE line_items SET label = 'expected label'", new ResultSetMapping())
            ->execute();

        $this->assertEquals(0, $this->commandTester->execute(
            [
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

            $this->assertSame('expected label', $user->getLastAssignment()->getLineItem()->getLabel());
        }
    }

    public function testItCanWarmUpResultCacheByListOfUsers(): void
    {
        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => '1',
                '--user-ids' => '1,2,3,4,5,6,7,8,9,10',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 10; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            $this->assertTrue($this->doctrineResultCache->contains($userCacheId));
        }

        for ($i = 11; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            $this->assertFalse($this->doctrineResultCache->contains($userCacheId));
        }

        $this->assertStringContainsString(
            '[OK] 10 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItCanWarmUpResultCacheByListOfLineItems(): void
    {
        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => '1',
                '--line-item-ids' => '1,2',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        for ($i = 1; $i <= 60; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            $this->assertTrue($this->doctrineResultCache->contains($userCacheId));
        }

        for ($i = 61; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            $this->assertFalse($this->doctrineResultCache->contains($userCacheId));
        }

        $this->assertStringContainsString(
            '[OK] 60 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItStopsExecutionIfCriteriaDoNotMatchAnyCacheEntries(): void
    {
        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--line-item-ids' => '999',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        $this->assertStringContainsString(
            '[OK] No matching cache entries, exiting.',
            $this->commandTester->getDisplay()
        );
    }

    public function testItThrowsExceptionIfBothLineItemIdAndUserIdFiltersAreSpecified(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'user-ids' and 'line-item-ids' are exclusive options, please specify only one of them");

        $this->commandTester->execute(
            [
                '--batch-size' => '1',
                '--user-ids' => '61,62,63',
                '--line-item-ids' => '1,2',
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
        $this->expectExceptionMessage("Invalid 'user-ids' option received.");

        $this->commandTester->execute(
            [
                '--user-ids' => $invalidOptionValue,
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
        $this->expectExceptionMessage("Invalid 'line-item-ids' option received.");

        $this->commandTester->execute(
            [
                '--line-item-ids' => $invalidOptionValue,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function provideInvalidFilterOption(): array
    {
        return [
            ['adsffdsfs'],
            [','],
            [',,,,,,'],
        ];
    }
}
