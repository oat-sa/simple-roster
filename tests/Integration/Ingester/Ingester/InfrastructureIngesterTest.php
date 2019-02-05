<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\Infrastructure;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Tests\Traits\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InfrastructureIngesterTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var InfrastructureIngester */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->subject = new InfrastructureIngester($this->getEntityManager());
    }

    public function testDryRunIngest()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->ingest($source);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('infrastructure', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertCount(3, $output->getSuccesses());
        $this->assertCount(0, $output->getFailures());

        $this->assertEmpty($this->getRepository(Infrastructure::class)->findAll());
    }

    public function testIngest()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('infrastructure', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertCount(3, $output->getSuccesses());
        $this->assertCount(0, $output->getFailures());

        $this->assertCount(3, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());

        $user2 = $this->getRepository(Infrastructure::class)->find(2);
        $this->assertEquals('infra_2', $user2->getLabel());

        $user3 = $this->getRepository(Infrastructure::class)->find(3);
        $this->assertEquals('infra_3', $user3->getLabel());
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}