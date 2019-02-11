<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\LineItem;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Result\IngesterResult;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Tests\Traits\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LineItemIngesterTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var LineItemIngester */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->subject = new LineItemIngester($this->getManagerRegistry());
    }

    public function testDryRunIngest(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv');

        $output = $this->subject->ingest($source);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('line-item', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertEquals(6, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertEmpty($this->getRepository(LineItem::class)->findAll());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot ingest 'line-item' since infrastructure table is empty.
     */
    public function testIngestWithEmptyInfrastructures(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv.csv');

        $this->subject->ingest($source, false);
    }

    public function testIngestWithInvalidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/line-items.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('line-item', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(1, $output->getSuccessCount());
        $this->assertTrue($output->hasFailures());
        $this->assertCount(1, $output->getFailures());

        $this->assertCount(1, $this->getRepository(LineItem::class)->findAll());

        $lineItem1 = $this->getRepository(LineItem::class)->find(1);
        $this->assertEquals('gra13_ita_1', $lineItem1->getSlug());

        $failure = current($output->getFailures());
        $this->assertEquals(2, $failure->getLineNumber());
        $this->assertEquals(
            [
                'http://taoplatform.loc/delivery_2.rdf',
                'label2',
                'gra13_ita_1',
                'infra_2',
                '2019-01-05 10:00:00',
                '2019-01-26 18:30:00'
            ],
            $failure->getData()
        );
        $this->assertContains('UNIQUE constraint failed: line_items.slug', $failure->getReason());
    }

    public function testIngestWithValidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertInstanceOf(IngesterResult::class, $output);
        $this->assertEquals('line-item', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(6, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertCount(6, $this->getRepository(LineItem::class)->findAll());

        $lineItem1 = $this->getRepository(LineItem::class)->find(1);
        $this->assertEquals('gra13_ita_1', $lineItem1->getSlug());

        $lineItem6 = $this->getRepository(LineItem::class)->find(6);
        $this->assertEquals('gra13_ita_6', $lineItem6->getSlug());
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(InfrastructureIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv'),
            false
        );
    }
}