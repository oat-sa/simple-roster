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
use App\Tests\Traits\DatabaseTestingTrait;
use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DoctrineResultCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var Cache */
    private $doctrineResultCache;

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
        $doctrineResultCache = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$doctrineResultCache instanceof Cache) {
            throw new LogicException('Doctrine result cache is not configured.');
        }
        $this->doctrineResultCache = $doctrineResultCache;

        $this->userCacheIdGenerator = self::$container->get(UserCacheIdGenerator::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('100usersWithAssignments.yml');
    }

    public function testItCanWarmResultCacheForAllUsers(): void
    {
        self::assertSame(0, $this->commandTester->execute(
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

            self::assertTrue($this->doctrineResultCache->contains($userCacheId));
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

            self::assertTrue($this->doctrineResultCache->contains($userCacheId));
        }

        for ($i = 11; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertFalse($this->doctrineResultCache->contains($userCacheId));
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

            self::assertTrue($this->doctrineResultCache->contains($userCacheId));
        }

        for ($i = 61; $i <= 100; $i++) {
            $username = sprintf('user_%d', $i);
            $userCacheId = $this->userCacheIdGenerator->generate($username);

            self::assertFalse($this->doctrineResultCache->contains($userCacheId));
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
                '--line-item-slugs' => $invalidOptionValue,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );
    }

    public function provideInvalidFilterOption(): array
    {
        return [
            [','],
            [',,,,,,'],
        ];
    }
}
