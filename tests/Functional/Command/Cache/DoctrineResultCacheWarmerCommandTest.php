<?php declare(strict_types=1);

namespace App\Tests\Functional\Command\Cache;

use App\Command\Cache\DoctrineResultCacheWarmerCommand;
use App\Tests\Traits\DatabaseManualFixturesTrait;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DoctrineResultCacheWarmerCommandTest extends KernelTestCase
{
    use DatabaseManualFixturesTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp()
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find(DoctrineResultCacheWarmerCommand::NAME));

        $this->loadFixtures([
            __DIR__ . '/../../../../fixtures/100usersWithAssignments.yml',
        ]);
    }

    public function testItIteratesThroughAllUsers(): void
    {
        $this->assertEquals(0, $this->commandTester->execute(['--batch-size' => 6]));
        $this->assertContains(
            '[OK] 100 result cache entries have been successfully warmed up.',
            $this->commandTester->getDisplay()
        );
    }

    public function testOutputInCaseOfException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'batch-size' argument received.");

        $this->commandTester->execute(['--batch-size' => -1]);
    }
}
