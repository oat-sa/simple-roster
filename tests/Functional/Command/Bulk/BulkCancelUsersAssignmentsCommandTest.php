<?php declare(strict_types=1);

namespace App\Tests\Functional\Command\Bulk;

use App\Command\Bulk\BulkCancelUsersAssignmentsCommand;
use App\Entity\Assignment;
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

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();
        $this->setUpTestLogHandler();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(BulkCancelUsersAssignmentsCommand::NAME));

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
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

        $this->assertEquals(0, $output);
        $this->assertStringContainsString(
            'Processed: 100, batched errors: 0',
            $this->commandTester->getDisplay()
        );
        $this->assertStringContainsString(
            "[OK] Successfully cancelled '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);
        $this->assertCount(100, $assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
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
            "[OK] Successfully cancelled '100' assignments out of '100'.",
            $this->commandTester->getDisplay()
        );

        $assignmentRepository = $this->getRepository(Assignment::class);
        $this->assertCount(0, $assignmentRepository->findBy(['state' => Assignment::STATE_CANCELLED]));
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
                'path' => __DIR__ . '/../../../Resources/Assignment/100-users.csv',
                '--batch' => 5,
            ]
        );
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
    }
}
