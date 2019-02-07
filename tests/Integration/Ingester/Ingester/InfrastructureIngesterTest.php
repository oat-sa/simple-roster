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

        $this->subject = new InfrastructureIngester($this->getManagerRegistry());
    }

    public function testDryRunIngest()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->ingest($source);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('infrastructure', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertEquals(3, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertEmpty($this->getRepository(Infrastructure::class)->findAll());
    }

    public function testIngestWithValidSource()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('infrastructure', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(3, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertCount(3, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());

        $user2 = $this->getRepository(Infrastructure::class)->find(2);
        $this->assertEquals('infra_2', $user2->getLabel());

        $user3 = $this->getRepository(Infrastructure::class)->find(3);
        $this->assertEquals('infra_3', $user3->getLabel());
    }

    public function testIngestWithInvalidSource()
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/infrastructures.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('infrastructure', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(1, $output->getSuccessCount());
        $this->assertTrue($output->hasFailures());

        $this->assertCount(1, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        $this->assertEquals('infra_1', $user1->getLabel());

        $failure = current($output->getFailures());
        $this->assertEquals(2, $failure->getLineNumber());
        $this->assertEquals(
            ['infra_2', 'http://infra_2.com', 'key2'],
            $failure->getData()
        );
        $this->assertContains('Undefined offset: 3', $failure->getReason());
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}