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

namespace App\Tests\Functional\Command\Bulk;

use App\Command\Bulk\BulkCreateUsersAssignmentsCommand;
use App\Entity\Assignment;
use App\Entity\User;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use App\Tests\Traits\LoggerTestingTrait;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BulkCreateUsersAssignmentsCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;
    use LoggerTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();
        $this->setUpTestLogHandler();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(BulkCreateUsersAssignmentsCommand::NAME));

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
    }

    public function testItCanCreateNewAssignmentsForUsersAlreadyHavingAssignments(): void
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[OK] Successfully processed '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        /** @var User $user */
        foreach ($this->getRepository(User::class)->findAll() as $user) {
            $userAssignments = $user->getAssignments();

            $this->assertCount(2, $userAssignments);
            $this->assertEquals(Assignment::STATE_CANCELLED, $userAssignments[0]->getState());
            $this->assertEquals(Assignment::STATE_READY, $userAssignments[1]->getState());
        }
    }

    public function testItLogsSuccessfulAssignmentCreations(): void
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

        $this->assertEquals(0, $output);

        for ($i = 1; $i <= 100; $i++) {
            $username = sprintf('user_%s', $i);
            $this->assertHasLogRecordWithMessage(
                sprintf("Successful assignment creation (username = '%s').", $username),
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[OK] Successfully processed '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);
        $this->assertCount(100, $assignmentRepository->findAll());
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

        $this->assertEquals(1, $output);
        $this->assertStringContainsString(
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
                '--force' => 'true'
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[OK] Successfully processed '85' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );
    }
}
