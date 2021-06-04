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

use OAT\SimpleRoster\Command\Ingester\AssignmentIngesterCommand;
use OAT\SimpleRoster\Command\Ingester\LineItemIngesterCommand;
use OAT\SimpleRoster\Command\Ingester\UserIngesterCommand;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Tests\Traits\CommandDisplayNormalizerTrait;
use OAT\SimpleRoster\Tests\Traits\CsvIngestionTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AssignmentIngesterCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use CsvIngestionTestingTrait;
    use CommandDisplayNormalizerTrait;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(AssignmentIngesterCommand::NAME));

        $this->setUpDatabase();
    }

    /**
     * @throws ReflectionException
     */
    public function testItDoesNotIngestInDryRun(): void
    {
        $this->prepareLineItemIngestionContext();
        $this->prepareUserIngestionContext();

        $assignmentsCsvContent = [
            ['username', 'lineItemSlug'],
            ['user_1', 'gra13_ita_1'],
            ['user_1', 'gra13_ita_2'],
            ['user_1', 'gra13_ita_2'],
            ['user_2', 'gra13_ita_2'],
            ['user_2', 'gra13_ita_3'],
            ['user_2', 'gra13_ita_4'],
            ['user_2', 'gra13_ita_5'],
            ['user_2', 'gra13_ita_5'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_2'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_6'],
            ['user_3', 'gra13_ita_6'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_1'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_3'],
        ];

        $this->writeCsv('assignments.csv', $assignmentsCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'assignments.csv',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(0, $this->getRepository(Assignment::class)->findAll());

        self::assertStringContainsString(
            'Simple Roster - Assignment Ingester',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            'Executing ingestion...',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
        self::assertStringContainsString(
            '[WARNING] [DRY RUN] 18 assignments have been successfully ingested.',
            $this->normalizeDisplay($this->commandTester->getDisplay(true))
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testSuccessfulBatchedIngestion(): void
    {
        $this->prepareLineItemIngestionContext();
        $this->prepareUserIngestionContext();

        $assignmentsCsvContent = [
            ['username', 'lineItemSlug'],
            ['user_1', 'gra13_ita_1'],
            ['user_1', 'gra13_ita_2'],
            ['user_1', 'gra13_ita_2'],
            ['user_2', 'gra13_ita_2'],
            ['user_2', 'gra13_ita_3'],
            ['user_2', 'gra13_ita_4'],
            ['user_2', 'gra13_ita_5'],
            ['user_2', 'gra13_ita_5'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_2'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_6'],
            ['user_3', 'gra13_ita_6'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_1'],
            ['user_3', 'gra13_ita_3'],
            ['user_3', 'gra13_ita_3'],
        ];

        $this->writeCsv('assignments.csv', $assignmentsCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'assignments.csv',
                '--batch' => 4,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertCount(18, $this->getRepository(Assignment::class)->findAll());
        self::assertStringContainsString(
            '[OK] 18 assignments have been successfully ingested.',
            $this->commandTester->getDisplay(true)
        );

        $expectedAssignmentCounts = [
            'user_1' => 3,
            'user_2' => 5,
            'user_3' => 10,
        ];

        foreach ($expectedAssignmentCounts as $username => $expectedAssignmentCount) {
            $user = $this->getRepository(User::class)->findOneBy(['username' => $username]);
            $assignments = $user->getAssignments();

            self::assertCount($expectedAssignmentCount, $assignments);

            /** @var Assignment $assignment */
            foreach ($assignments as $assignment) {
                self::assertSame(Assignment::STATE_READY, $assignment->getState());
                self::assertSame(0, $assignment->getAttemptsCount());
            }
        }
    }

    /**
     * @dataProvider provideInvalidSourceFiles
     */
    public function testSourceFileValidation(string $filename, array $csvContent, string $expectedOutput): void
    {
        $this->prepareLineItemIngestionContext();
        $this->prepareUserIngestionContext();

        $this->writeCsv($filename, $csvContent);

        $output = $this->commandTester->execute(
            [
                'path' => $filename,
                '--batch' => 3,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertStringContainsString($expectedOutput, $this->commandTester->getDisplay(true));
    }

    public function testItThrowsExceptionIfNoUsersAreFound(): void
    {
        $this->prepareLineItemIngestionContext();

        $assignmentsCsvContent = [
            ['username', 'lineItemSlug'],
            ['user_1', 'gra13_ita_1'],
        ];

        $this->writeCsv('assignments.csv', $assignmentsCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'assignments.csv',
                '--batch' => 1,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertCount(0, $this->getRepository(Assignment::class)->findAll());
        self::assertStringContainsString(
            "[ERROR] User with username 'user_1' cannot not found.",
            $this->commandTester->getDisplay(true)
        );
    }

    public function testItThrowsExceptionIfNoLineItemsAreFound(): void
    {
        $this->prepareUserIngestionContext();

        $assignmentsCsvContent = [
            ['username', 'lineItemSlug'],
            ['user_1', 'gra13_ita_1'],
        ];

        $this->writeCsv('assignments.csv', $assignmentsCsvContent);

        $output = $this->commandTester->execute(
            [
                'path' => 'assignments.csv',
                '--batch' => 1,
                '--force' => true,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertCount(0, $this->getRepository(Assignment::class)->findAll());
        self::assertStringContainsString(
            '[ERROR] No line items were found in database.',
            $this->commandTester->getDisplay(true)
        );
    }

    public function provideInvalidSourceFiles(): array
    {
        return [
            'usernameColumnIsMissing' => [
                'filename' => 'assignments-without-username-column.csv',
                'csvContent' => [
                    ['lineItemSlug'],
                    ['gra13_ita_1'],
                ],
                'expectedOutput' => "[ERROR] Column 'username' is not set in source file.",
            ],
            'lineItemSlugColumnIsMissing' => [
                'filename' => 'assignments-without-lineItemSlug-column.csv',
                'csvContent' => [
                    ['username'],
                    ['user_1'],
                ],
                'expectedOutput' => "[ERROR] Column 'lineItemSlug' is not set in source file.",
            ],
        ];
    }

    private function prepareLineItemIngestionContext(): void
    {
        $lineItemsCsvContent = [
            ['uri', 'label', 'slug', 'startTimestamp', 'endTimestamp', 'maxAttempts'],
            ['http://taoplatform.loc/delivery_1.rdf', 'label1', 'gra13_ita_1', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_2.rdf', 'label2', 'gra13_ita_2', 1546682400, 1546713000, 2],
            ['http://taoplatform.loc/delivery_3.rdf', 'label2', 'gra13_ita_3', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_4.rdf', 'label4', 'gra13_ita_4', 1546682400, 1546713000, 2],
            ['http://taoplatform.loc/delivery_5.rdf', 'label5', 'gra13_ita_5', 1546682400, 1546713000, 1],
            ['http://taoplatform.loc/delivery_6.rdf', 'label6', 'gra13_ita_6', 1546682400, 1546713000, 2],
        ];

        $this->writeCsv('line-items.csv', $lineItemsCsvContent);

        self::assertSame(0, $this->ingestCsv(LineItemIngesterCommand::NAME, 'line-items.csv'));
    }

    private function prepareUserIngestionContext(): void
    {
        $usersCsvContent = [
            ['username', 'password'],
            ['user_1', 'password1'],
            ['user_2', 'password2'],
            ['user_3', 'password3'],
        ];

        $this->writeCsv('users.csv', $usersCsvContent);

        self::assertSame(0, $this->ingestCsv(UserIngesterCommand::NAME, 'users.csv'));
    }
}
