<?php declare(strict_types=1);

namespace App\Tests\Functional\Command\Ingester;

use App\Entity\Infrastructure;
use App\Tests\Traits\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IngesterCommandTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp()
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();
        $application = new Application($kernel);

        $this->commandTester = new CommandTester($application->find('roster:ingest'));
    }


    public function testDryRunLocalIngestion()
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv',
        ]);

        $this->assertEquals(0, $output);
        $this->assertContains(
            "[OK] [DRY_RUN] Ingestion (type='infrastructure', source='local'): 3 successes, 0 failures.",
            $this->commandTester->getDisplay()
        );
    }

    public function testLocalIngestionSuccess()
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv',
            '--force' => true
        ]);

        $this->assertEquals(0, $output);
        $this->assertContains(
            "[OK] Ingestion (type='infrastructure', source='local'): 3 successes, 0 failures.",
            $this->commandTester->getDisplay()
        );

        $this->assertCount(3, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());

        $user2 = $this->getRepository(Infrastructure::class)->find(2);
        $this->assertEquals('infra_2', $user2->getLabel());

        $user3 = $this->getRepository(Infrastructure::class)->find(3);
        $this->assertEquals('infra_3', $user3->getLabel());
    }

    public function testLocalIngestionFailure()
    {
        $output = $this->commandTester->execute([
            'type' => 'infrastructure',
            'source' => 'local',
            'path' => __DIR__ . '/../../../Resources/Ingester/Invalid/infrastructures.csv',
            '--force' => true
        ]);

        $this->assertEquals(0, $output);
        $this->assertContains(
            "[WARNING] Ingestion (type='infrastructure', source='local'): 1 successes, 1 failures.",
            $this->commandTester->getDisplay()
        );

        $this->assertContains("Undefined offset: 3", $this->commandTester->getDisplay());

        $this->assertCount(1, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());
    }
}