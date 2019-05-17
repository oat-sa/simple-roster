<?php declare(strict_types=1);
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

namespace App\Tests\Functional\Command\GarbageCollector;

use App\Command\GarbageCollector\AssignmentGarbageCollectorCommand;
use App\Entity\Assignment;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use App\Tests\Traits\LoggerTestingTrait;
use Carbon\Carbon;
use DateTime;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AssignmentGarbageCollectorCommandTest extends KernelTestCase
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
        $this->commandTester = new CommandTester($application->find(AssignmentGarbageCollectorCommand::NAME));

        Carbon::setTestNow((new DateTime())->format('Y-m-d H:i:s'));
    }

    public function testOutputWhenThereIsNothingToUpdate(): void
    {
        $this->assertEquals(0, $this->commandTester->execute([]));
        $this->assertStringContainsString(
            '[OK] Nothing to update.',
            $this->commandTester->getDisplay()
        );
    }

    public function testDryRunDoesNotUpdateAssignmentState(): void
    {
        $this->loadTestFixtures();

        $this->assertEquals(0, $this->commandTester->execute([]));
        $this->assertStringContainsString(
            "[OK] Total of '10' stuck assignments were successfully marked as 'completed'.",
            $this->commandTester->getDisplay()
        );

        $this->assertCount(
            10,
            $this->getRepository(Assignment::class)->findBy(['state' => Assignment::STATE_STARTED])
        );
    }

    public function testItCanUpdateStuckAssignments(): void
    {
        $this->loadTestFixtures();

        $this->assertEquals(0, $this->commandTester->execute(
            [
                '--batch-size' => 1,
                '--force' => 'true', // Test if it gets casted properly
            ]
        ));
        $this->assertStringContainsString(
            "[OK] Total of '10' stuck assignments were successfully marked as 'completed'.",
            $this->commandTester->getDisplay()
        );

        for ($i = 1; $i <= 10; $i++) {
            $this->assertHasLogRecordWithMessage(
                sprintf(
                    "Assignment with id='%s' of user with username='%s' has been marked as completed by garbage collector.",
                    $i,
                    'userWithStartedButStuckAssignment_' . $i
                ),
                Logger::INFO
            );
        }

        /** @var Assignment $assignment */
        foreach ($this->getRepository(Assignment::class)->findAll() as $assignment) {
            $this->assertEquals(Assignment::STATE_COMPLETED, $assignment->getState());
            $this->assertEquals(Carbon::now()->toDateTime(), $assignment->getUpdatedAt());
        }
    }

    public function testItCanUpdateStuckAssignmentsInMultipleBatch(): void
    {
        $this->loadTestFixtures();

        $this->assertEquals(
            0,
            $this->commandTester->execute(
                [
                    '--force' => true,
                    '--batch-size' => '3', // Test it gets casted properly
                ]
            )
        );
        $this->assertStringContainsString(
            "[OK] Total of '10' stuck assignments were successfully marked as 'completed'.",
            $this->commandTester->getDisplay()
        );

        /** @var Assignment $assignment */
        foreach ($this->getRepository(Assignment::class)->findAll() as $assignment) {
            $this->assertEquals(Assignment::STATE_COMPLETED, $assignment->getState());
            $this->assertEquals(Carbon::now()->toDateTime(), $assignment->getUpdatedAt());
        }
    }

    public function testOutputInCaseOfException(): void
    {
        $this->assertEquals(1, $this->commandTester->execute(['--batch-size' => 0]));
        $this->assertStringContainsString(
            "[ERROR] Invalid 'batch-size' argument received.",
            $this->commandTester->getDisplay()
        );
    }

    private function loadTestFixtures(): void
    {
        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/usersWithStartedButStuckAssignments.yml',
        ]);
    }
}
