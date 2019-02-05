<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\User;
use App\Ingester\Ingester\UserIngester;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Tests\Traits\DatabaseFixturesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserIngesterTest extends KernelTestCase
{
    use DatabaseFixturesTrait;

    /** @var UserIngester */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabaseAndFixture();

        $this->subject = new UserIngester($this->getEntityManager());
    }

    public function testDryRunIngest()
    {
        $source = $this->createLocalIngesterSource(__DIR__ . '/../../../Resources/Ingester/users.csv');

        $output = $this->subject->ingest($source);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertTrue($output->isDryRun());
        $this->assertCount(3, $output->getSuccesses());
        $this->assertCount(0, $output->getFailures());

        $this->assertEmpty($this->getRepository(User::class)->findAll());
    }

    public function testIngest()
    {
        $source = $this->createLocalIngesterSource(__DIR__ . '/../../../Resources/Ingester/users.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertFalse($output->isDryRun());
        $this->assertCount(3, $output->getSuccesses());
        $this->assertCount(0, $output->getFailures());

        $this->assertCount(3, $this->getRepository(User::class)->findAll());

        $user1 = $this->getRepository(User::class)->find(1);
        $this->assertEquals('user_1', $user1->getUsername());

        $user2 = $this->getRepository(User::class)->find(2);
        $this->assertEquals('user_2', $user2->getUsername());

        $user3 = $this->getRepository(User::class)->find(3);
        $this->assertEquals('user_3', $user3->getUsername());
    }

    private function createLocalIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}