<?php declare(strict_types=1);

namespace App\Tests\Functional\Command\Bulk;

use App\Command\Bulk\BulkCancelUsersAssignmentsCommand;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class BulkCancelUsersAssignmentsCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(BulkCancelUsersAssignmentsCommand::NAME));

        // TODO
        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
    }
}
