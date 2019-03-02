<?php declare(strict_types=1);

namespace App\Tests\Functional\Command\Ingester\Native;

use App\Command\Ingester\Native\NativeUserIngesterCommand;
use App\Entity\User;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Tests\Traits\DatabaseTrait;
use Doctrine\ORM\Query\ResultSetMapping;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class NativeUserIngesterCommandTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var CommandTester */
    private $commandTester;

    protected function setUp()
    {
        parent::setUp();

        $kernel = $this->setUpDatabase();
        $application = new Application($kernel);

        $this->commandTester = new CommandTester($application->find(NativeUserIngesterCommand::NAME));
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
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
            ]
        );
    }

    public function testNonBatchedLocalIngestionSuccess(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertContains(
            'Total of users imported: 12, batched errors: 0',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        $this->assertEquals('user_12', $user12->getUsername());
    }

    public function testBatchedLocalIngestionSuccess(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
                '--batch' => 2,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertContains(
            'Total of users imported: 12, batched errors: 0',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(12, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user12 = $this->getRepository(User::class)->find(12);
        $this->assertEquals('user_12', $user12->getUsername());
    }

    public function testBatchedLocalIngestionFailureWithEmptyLineItems(): void
    {
        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Valid/users.csv',
                '--batch' => 1,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(1, $output);
        $this->assertContains(
            "[ERROR] Cannot native ingest 'user' since line-item table is empty.",
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );
    }

    public function testBatchedLocalIngestionFailureWithInvalidUsers(): void
    {
        $this->prepareIngestionContext();

        $output = $this->commandTester->execute(
            [
                'source' => 'local',
                'path' => __DIR__ . '/../../../../Resources/Ingester/Invalid/users.csv',
                '--batch' => 1,
            ],
            [
                'capture_stderr_separately' => true,
            ]
        );

        $this->assertEquals(0, $output);
        $this->assertContains(
            'Total of users imported: 1, batched errors: 1',
            $this->normalizeDisplay($this->commandTester->getDisplay())
        );

        $this->assertCount(1, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());
    }

    /**
     * Without this tests asserting the command display are failing with plain phpunit (so NOT with bin/phpunit)
     * due to new line/tab characters. This modification does NOT affect bin/phpunit usage.
     */
    private function normalizeDisplay(string $commandDisplay): string
    {
        return trim(preg_replace('/\s+/', ' ', $commandDisplay));
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(InfrastructureIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../../Resources/Ingester/Valid/infrastructures.csv'),
            false
        );

        static::$container->get(LineItemIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../../Resources/Ingester/Valid/line-items.csv'),
            false
        );
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}
