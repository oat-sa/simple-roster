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

namespace OAT\SimpleRoster\Tests\Functional\Command\Ingester;

use OAT\SimpleRoster\Command\Ingester\UserIngesterCommand;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\CsvIngestionTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UserIngesterCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use CommandDisplayNormalizerTrait;
    use CsvIngestionTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(UserIngesterCommand::NAME));

        $this->setUpDatabase();
    }

    /**
     * @throws ReflectionException
     */
    public function testItDoesNotIngestInDryRun(): void
    {
        $usersCsvContent = [
            ['username', 'password'],
            ['user_1', 'password_1'],
            ['user_2', 'password_2'],
            ['user_3', 'password_3'],
            ['user_4', 'password_4'],
            ['user_5', 'password_5'],
            ['user_6', 'password_6'],
            ['user_7', 'password_7'],
            ['user_8', 'password_8'],
            ['user_9', 'password_9'],
            ['user_10', 'password_10'],
        ];

        $this->writeCsv('users.csv', $usersCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'users.csv',
                '--storage' => 'test',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(0, $this->getRepository(User::class)->findAll());
        self::assertStringContainsString(
            '[WARNING] [DRY RUN] 10 users have been successfully ingested.',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testSuccessfulBatchedIngestion(): void
    {
        $usersCsvContent = [
            ['username', 'password'],
            ['user_1', 'password_1'],
            ['user_2', 'password_2'],
            ['user_3', 'password_3'],
            ['user_4', 'password_4'],
            ['user_5', 'password_5'],
            ['user_6', 'password_6'],
            ['user_7', 'password_7'],
            ['user_8', 'password_8'],
            ['user_9', 'password_9'],
            ['user_10', 'password_10'],
        ];

        $this->writeCsv('users.csv', $usersCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'users.csv',
                '--storage' => 'test',
                '--batch' => 4,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(10, $this->getRepository(User::class)->findAll());
        self::assertStringContainsString(
            '[OK] 10 users have been successfully ingested.',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );
    }

    /**
     * @dataProvider provideInvalidSourceFiles
     */
    public function testSourceFileValidation(string $filename, array $csvContent, string $expectedOutput): void
    {
        $this->writeCsv($filename, $csvContent);

        $output = $this->commandTester->execute(
            [
                'path' => $filename,
                '--storage' => 'test',
                '--batch' => 3,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertStringContainsString($expectedOutput, $this->normalizeDisplay($this->commandTester->getDisplay()));
    }

    public function provideInvalidSourceFiles(): array
    {
        return [
            'usernameColumnIsMissing' => [
                'filename' => 'users-without-username-column.csv',
                'csvContent' => [
                    ['password'],
                    ['password_1'],
                ],
                'expectedOutput' => "[ERROR] Column 'username' is not set in source file.",
            ],
            'passwordColumnIsMissing' => [
                'filename' => 'users-without-password-column.csv',
                'csvContent' => [
                    ['username'],
                    ['user_1'],
                ],
                'expectedOutput' => "[ERROR] Column 'password' is not set in source file.",
            ],
        ];
    }
}
