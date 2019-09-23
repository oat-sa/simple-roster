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

namespace App\Tests\Functional\Command\Ingester\Native;

use App\Command\Ingester\Native\NativeUserIngesterCommand;
use App\Entity\User;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseTrait;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class NativeUserIngesterCommandTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();
        $application = new Application($kernel);

        $this->commandTester = new CommandTester($application->find(NativeUserIngesterCommand::NAME));
    }

    public function testItThrowsExceptionIfNoConsoleOutputWasFound(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Output must be instance of 'Symfony\Component\Console\Output\ConsoleOutputInterface' because of section usage."
        );

        $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
            ]
        );
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Total of users imported: 0, batched errors: 0',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(0, $this->getRepository(User::class)->findAll());
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Total of users imported: 12, batched errors: 0',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        $this->assertEquals('user_12', $user12->getUsername());
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Total of users imported: 12, batched errors: 0',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        $this->assertEquals('user_12', $user12->getUsername());
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Total of users imported: 12, batched errors: 0',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $this->assertCount(12, $userRepository->findAll());

        $user1 = $userRepository->find(1);
        $this->assertSame('user_1', $user1->getUsername());
        $this->assertSame('group_1', $user1->getGroupId());

        $user6 = $userRepository->find(6);
        $this->assertSame('user_6', $user6->getUsername());
        $this->assertSame('group_2', $user6->getGroupId());

        $user12 = $userRepository->find(12);
        $this->assertSame('user_12', $user12->getUsername());
        $this->assertSame('group_3', $user12->getGroupId());
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

        $this->assertEquals(1, $output);
        $this->assertStringContainsString(
            "[ERROR] Cannot native ingest 'user' since line-item table is empty.",
            $this->normalizeDisplay($this->commandTester->getDisplay())
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Total of users imported: 1, batched errors: 1',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(1, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());
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
