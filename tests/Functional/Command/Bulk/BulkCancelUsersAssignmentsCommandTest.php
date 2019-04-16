<?php declare(strict_types=1);

namespace App\Tests\Functional\Command\Bulk;

use App\Command\Bulk\BulkCancelUsersAssignmentsCommand;
use App\Entity\Assignment;
use App\Repository\AssignmentRepository;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use App\Tests\Traits\LoggerTestingTrait;
use LogicException;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BulkCancelUsersAssignmentsCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;
    use LoggerTestingTrait;

    /** @var CommandTester */
    private $commandTester;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();
        $this->setUpTestLogHandler();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(BulkCancelUsersAssignmentsCommand::NAME));
        $this->assignmentRepository = $this->getRepository(Assignment::class);

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
    }

    public function testItCanCancelUserAssignments(): void
    {
        $this->commandTester->setInputs(['yes']);

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/users-100.csv',
                '--batch' => 3,
                '--force' => 'true',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Processed: 100, batched errors: 0',
            $this->commandTester->getDisplay()
        );
        $this->assertStringContainsString(
            "[OK] Successfully cancelled '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $this->assertCount(100, $this->assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
    }

    public function testItLogsSuccessfulAssignmentCancellations(): void
    {
        $this->commandTester->setInputs(['yes']);

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/users-100.csv',
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
                sprintf("Successful assignment cancellation (assignmentId = '%s', username = '%s')", $i, $username),
                Logger::INFO
            );

        }
    }

    public function testItDoesNotApplyDatabaseModificationsInDryMode(): void
    {
        $this->commandTester->setInputs(['yes']);

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/users-100.csv',
                '--batch' => 5,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            "[OK] Successfully cancelled '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $this->assertCount(0, $this->assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
    }

    public function testItAbortsTheProcessIfDoesNotWantToProceed(): void
    {
        $this->commandTester->setInputs(['no']);

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/users-100.csv',
                '--batch' => 5,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            '[OK] Aborting.',
            $this->commandTester->getDisplay()
        );
        $this->assertStringNotContainsString(
            "[OK] Successfully cancelled '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $this->assertCount(0, $this->assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
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
                'path' => __DIR__ . '/../../../Resources/Assignment/users-100.csv',
                '--batch' => 5,
            ]
        );
    }

    public function testItThrowsRuntimeExceptionIfUsernameColumnCannotBeFoundInSourceCsvFile(): void
    {
        $this->commandTester->setInputs(['yes']);

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
        $this->commandTester->setInputs(['yes']);

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../Resources/Assignment/users-100-with-invalid.csv',
                '--batch' => 3,
                '--force' => 'true',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Processed: 100, batched errors: 5',
            $this->commandTester->getDisplay()
        );
        $this->assertStringContainsString(
            "[OK] Successfully cancelled '85' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $this->assignmentRepository = $this->getRepository(Assignment::class);
    }
}
