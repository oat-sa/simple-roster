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

namespace App\Tests\Functional\Command\Bulk;

use App\Command\Bulk\BulkCancelUsersAssignmentsCommand;
use App\Entity\Assignment;
use App\Tests\Traits\DatabaseTestingTrait;
use App\Tests\Traits\LoggerTestingTrait;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BulkCancelUsersAssignmentsCommandTest extends KernelTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(BulkCancelUsersAssignmentsCommand::NAME));

        $this->setUpDatabase();
        $this->setUpTestLogHandler();
        $this->loadFixtureByFilename('100usersWithAssignments.yml');
    }

    public function testItCanCancelUserAssignments(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/100-users.csv',
                '--batch' => 3,
                '--force' => 'true',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertStringContainsString(
            "[OK] Successfully processed '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);
        self::assertCount(100, $assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
    }

    public function testItLogsSuccessfulAssignmentCancellations(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/100-users.csv',
                '--batch' => 5,
                '--force' => 'true',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);

        for ($i = 1; $i <= 100; $i++) {
            $username = sprintf('user_%s', $i);
            $this->assertHasLogRecordWithMessage(
                sprintf("Successful assignment cancellation (assignmentId = '%s', username = '%s')", $i, $username),
                Logger::INFO
            );
        }
    }

    public function testItDoesNotApplyDatabaseModificationsInDryMode(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/100-users.csv',
                '--batch' => 5,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertStringContainsString(
            "[OK] Successfully processed '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);
        self::assertCount(0, $assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
    }

    public function testItThrowsRuntimeExceptionIfUsernameColumnCannotBeFoundInSourceCsvFile(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(1, $output);
        self::assertStringContainsString(
            "[ERROR] Column 'username' cannot be found in source CSV file.",
            $this->commandTester->getDisplay()
        );
    }

    public function testItCanHandleErrorsDuringTheProcess(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/100-users-with-5-invalid.csv',
                '--batch' => 3,
                '--force' => 'true',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        self::assertSame(0, $output);
        self::assertStringContainsString(
            "[OK] Successfully processed '85' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );
    }
}
