<?php declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Command\AssignmentGarbageCollectorCommand;
use App\Entity\Assignment;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Carbon\Carbon;
use DateInterval;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AssignmentGarbageCollectorCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp()
    {
        parent::setUp();

        $kernel = static::bootKernel();
        $this->setUpDatabase($kernel);

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(AssignmentGarbageCollectorCommand::NAME));
    }

    public function testOutputWhenThereIsNothingToUpdate(): void
    {
        $this->assertEquals(0, $this->commandTester->execute([]));
        $this->assertContains(
            '[OK] Nothing to update.',
            $this->commandTester->getDisplay()
        );
    }

    public function testDryRunDoesNotUpdateAssignmentState(): void
    {
        $this->loadTestFixtures();

        $this->assertEquals(0, $this->commandTester->execute([]));
        $this->assertContains(
            '[OK] Total of `10` stuck assignments were successfully marked as `completed`.',
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

        $this->assertEquals(0, $this->commandTester->execute(['--force' => true]));
        $this->assertContains(
            '[OK] Total of `10` stuck assignments were successfully marked as `completed`.',
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);

        $this->assertCount(10, $assignmentRepository->findBy(['state' => Assignment::STATE_COMPLETED]));
        $this->assertEmpty($assignmentRepository->findBy(['state' => Assignment::STATE_STARTED]));
    }

    public function testItUpdateStuckAssignmentsInMultipleBatch(): void
    {
        $this->loadTestFixtures();

        $this->assertEquals(0, $this->commandTester->execute(['--force' => true, '--batch-size' => 3]));
        $this->assertContains(
            '[OK] Total of `10` stuck assignments were successfully marked as `completed`.',
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);

        $this->assertCount(10, $assignmentRepository->findBy(['state' => Assignment::STATE_COMPLETED]));
        $this->assertEmpty($assignmentRepository->findBy(['state' => Assignment::STATE_STARTED]));
    }

    private function loadTestFixtures(): void
    {
        $now = Carbon::now();
        Carbon::setTestNow(Carbon::now()->subtract(new DateInterval('P3D')));

        $this->loadFixtures([
            __DIR__ . '/../../../fixtures/usersWithStartedButStuckAssignments.yml',
        ]);

        Carbon::setTestNow($now);
    }
}
