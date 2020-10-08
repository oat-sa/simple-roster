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

namespace App\Tests\Functional\Command\Ingester\Native;

use App\Command\Ingester\Native\NativeUserIngesterCommand;
use App\Entity\User;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class NativeUserIngesterCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(NativeUserIngesterCommand::NAME));

        $this->setUpDatabase();
    }

    public function testItDoesNotIngestUsersInDryRun(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(0, $this->getRepository(User::class)->findAll());
    }

    public function testNonBatchedLocalIngestionSuccess(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        self::assertSame('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        self::assertSame('user_12', $user12->getUsername());
    }

    public function testBatchedLocalIngestionSuccess(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
                '--batch' => 2,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        self::assertSame('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        self::assertSame('user_12', $user12->getUsername());
    }

    public function testBatchedLocalIngestionWithGroupId(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users-with-groupId.csv',
                '--batch' => 2,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        self::assertCount(12, $userRepository->findAll());

        $user1 = $userRepository->find(1);
        self::assertInstanceOf(User::class, $user1);
        self::assertSame('user_1', $user1->getUsername());
        self::assertSame('group_1', $user1->getGroupId());

        $user6 = $userRepository->find(6);
        self::assertInstanceOf(User::class, $user6);
        self::assertSame('user_6', $user6->getUsername());
        self::assertSame('group_2', $user6->getGroupId());

        $user12 = $userRepository->find(12);
        self::assertInstanceOf(User::class, $user12);
        self::assertSame('user_12', $user12->getUsername());
        self::assertSame('group_3', $user12->getGroupId());
    }

    public function testBatchedLocalIngestionFailureWithEmptyLineItems(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
                '--batch' => 1,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertStringContainsString(
            "[ERROR] Cannot native ingest 'user' since line-item table is empty.",
            $this->commandTester->getDisplay()
        );
    }

    public function testBatchedLocalIngestionFailureWithInvalidUsers(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Invalid/users.csv',
                '--batch' => 1,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(1, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        self::assertEquals('user_1', $user1->getUsername());
    }

    public function testItCanIngestUsersWithMultipleAssignments(): void
    {
        $this->prepareIngestionContext();

        self::assertSame(0, $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users-with-multiple-assignments.csv',
                '--batch' => 2,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        ));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);

        $user1 = $userRepository->find(1);
        self::assertInstanceOf(User::class, $user1);
        self::assertCount(6, $user1->getAssignments());

        $user2 = $userRepository->find(2);
        self::assertInstanceOf(User::class, $user2);
        self::assertCount(4, $user2->getAssignments());

        $user3 = $userRepository->find(3);
        self::assertInstanceOf(User::class, $user3);
        self::assertCount(5, $user3->getAssignments());
    }

    /**
     * Without this tests asserting the command display are failing with plain phpunit (so NOT with bin/phpunit)
     * due to new line/tab characters. This modification does NOT affect bin/phpunit usage.
     */
    private function normalizeDisplay(string $commandDisplay): string
    {
        return trim((string)preg_replace('/\s+/', ' ', $commandDisplay));
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(InfrastructureIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../../Resources/Ingester/Valid/infrastructures.csv'),
            false
        );

        static::$container->get(LineItemIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../../Resources/Ingester/Valid/line-items.csv'),
            false
        );
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}
