<?php

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

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Ingester\Ingester;

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Ingester\Ingester\LineItemIngester;
use OAT\SimpleRoster\Ingester\Ingester\LtiInstanceIngester;
use OAT\SimpleRoster\Ingester\Source\IngesterSourceInterface;
use OAT\SimpleRoster\Ingester\Source\LocalCsvIngesterSource;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LineItemIngesterTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var LineItemIngester */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = new LineItemIngester($this->getManagerRegistry());
    }

    public function testDryRunIngest(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv');

        $output = $this->subject->ingest($source);

        self::assertSame('line-item', $output->getIngesterType());
        self::assertTrue($output->isDryRun());
        self::assertSame(6, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertEmpty($this->getRepository(LineItem::class)->findAll());
    }

    public function testIngestWithInvalidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/line-items.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame('line-item', $output->getIngesterType());
        self::assertFalse($output->isDryRun());
        self::assertSame(1, $output->getSuccessCount());
        self::assertTrue($output->hasFailures());
        self::assertCount(3, $output->getFailures());

        self::assertCount(1, $this->getRepository(LineItem::class)->findAll());

        $lineItem1 = $this->getRepository(LineItem::class)->find(1);
        self::assertSame('gra13_ita_1', $lineItem1->getSlug());

        $failure = current($output->getFailures());

        self::assertSame(2, $failure->getLineNumber());
        self::assertSame(
            [
                'uri' => 'http://taoplatform.loc/delivery_2.rdf',
                'label' => 'label2',
                'slug' => 'gra13_ita_1',
                'startTimestamp' => '1546682400',
                'endTimestamp' => '1546713000',
                'maxAttempts' => '0',
            ],
            $failure->getData()
        );

        self::assertStringContainsString('UNIQUE constraint failed: line_items.slug', $failure->getReason());
    }

    public function testIngestWithValidSource(): void
    {
        $this->prepareIngestionContext();

        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/line-items.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame('line-item', $output->getIngesterType());
        self::assertFalse($output->isDryRun());
        self::assertSame(6, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertCount(6, $this->getRepository(LineItem::class)->findAll());

        $lineItem1 = $this->getRepository(LineItem::class)->find(1);
        self::assertSame('gra13_ita_1', $lineItem1->getSlug());
        self::assertSame(1, $lineItem1->getMaxAttempts());

        $lineItem6 = $this->getRepository(LineItem::class)->find(6);
        self::assertSame('gra13_ita_6', $lineItem6->getSlug());
        self::assertSame(2, $lineItem6->getMaxAttempts());
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }

    private function prepareIngestionContext(): void
    {
        static::$container->get(LtiInstanceIngester::class)->ingest(
            $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv'),
            false
        );
    }
}
