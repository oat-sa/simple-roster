<?php declare(strict_types=1);
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Tests\Integration\Ingester\Ingester;

use App\Entity\LineItem;
use App\Ingester\Ingester\InfrastructureIngester;
use App\Ingester\Ingester\LineItemIngester;
use App\Ingester\Source\IngesterSourceInterface;
use App\Ingester\Source\LocalCsvIngesterSource;
use App\Tests\Traits\DatabaseTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LineItemIngesterTest extends KernelTestCase
{
    use DatabaseTrait;

    /** @var LineItemIngester */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->subject = new LineItemIngester($this->getManagerRegistry());
    }

    public function testDryRunIngest(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv');

        $output = $this->subject->ingest($source);

        $this->assertEquals('line-item', $output->getIngesterType());
        $this->assertTrue($output->isDryRun());
        $this->assertEquals(6, $output->getSuccessCount());
        $this->assertFalse($output->hasFailures());

        $this->assertEmpty($this->getRepository(LineItem::class)->findAll());
    }

    public function testIngestWithEmptyInfrastructures(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot ingest 'line-item' since infrastructure table is empty.");

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv.csv');

        $this->subject->ingest($source, false);
    }

    public function testIngestWithInvalidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/line-items.csv');

        $output = $this->subject->ingest($source, false);

        $this->assertEquals('line-item', $output->getIngesterType());
        $this->assertFalse($output->isDryRun());
        $this->assertEquals(1, $output->getSuccessCount());
        $this->assertTrue($output->hasFailures());
        $this->assertCount(3, $output->getFailures());

        $this->assertCount(1, $this->getRepository(LineItem::class)->findAll());

        $lineItem1 = $this->getRepository(LineItem::class)->find(1);
        $this->assertEquals('gra13_ita_1', $lineItem1->getSlug());

        $failure = current($output->getFailures());

        $this->assertEquals(2, $failure->getLineNumber());
        $this->assertEquals(
            [
                'uri' => 'http://taoplatform.loc/delivery_2.rdf',
                'label' => 'label2',
                'slug' => 'gra13_ita_1',
                'infrastructure' => 'infra_2',
                'startTimestamp' => '1546682400',
                'endTimestamp' => '1546713000'
            ],
            $failure->getData()
        );

        $this->assertStringContainsString('UNIQUE constraint failed: line_items.slug', $failure->getReason());
    }

    public function testIngestWithValidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv');

        $output = $this->subject->ingest($source, false);

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
