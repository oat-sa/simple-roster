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

use OAT\SimpleRoster\Entity\Infrastructure;
use OAT\SimpleRoster\Ingester\Ingester\InfrastructureIngester;
use OAT\SimpleRoster\Ingester\Source\IngesterSourceInterface;
use OAT\SimpleRoster\Ingester\Source\LocalCsvIngesterSource;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InfrastructureIngesterTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var InfrastructureIngester */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = new InfrastructureIngester($this->getManagerRegistry());
    }

    public function testDryRunIngest(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->ingest($source);

        self::assertSame('infrastructure', $output->getIngesterType());
        self::assertTrue($output->isDryRun());
        self::assertSame(3, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertEmpty($this->getRepository(Infrastructure::class)->findAll());
    }

    public function testIngestWithValidSource(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame('infrastructure', $output->getIngesterType());
        self::assertFalse($output->isDryRun());
        self::assertSame(3, $output->getSuccessCount());
        self::assertFalse($output->hasFailures());

        self::assertCount(3, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        self::assertSame('infra_1', $user1->getLabel());

        $user2 = $this->getRepository(Infrastructure::class)->find(2);
        self::assertSame('infra_2', $user2->getLabel());

        $user3 = $this->getRepository(Infrastructure::class)->find(3);
        self::assertSame('infra_3', $user3->getLabel());
    }

    public function testIngestWithInvalidSource(): void
    {
        $source = $this->createIngesterSource(__DIR__ . '/../../../Resources/Ingester/Invalid/infrastructures.csv');

        $output = $this->subject->ingest($source, false);

        self::assertSame('infrastructure', $output->getIngesterType());
        self::assertFalse($output->isDryRun());
        self::assertSame(1, $output->getSuccessCount());
        self::assertTrue($output->hasFailures());

        self::assertCount(1, $this->getRepository(Infrastructure::class)->findAll());

        $user1 = $this->getRepository(Infrastructure::class)->find(1);
        self::assertSame('infra_1', $user1->getLabel());

        $failure = current($output->getFailures());

        self::assertSame(2, $failure->getLineNumber());
        self::assertSame(
            [
                'label' => 'infra_2',
                'ltiDirectorLink' => 'http://infra_2.com',
                'ltiKey' => 'key2',
                'ltiSecret' => null
            ],
            $failure->getData()
        );

        $errorMessage = 'Argument 1 passed to OAT\SimpleRoster\Entity\Infrastructure::setLtiSecret() '
            . 'must be of the type string, null given';

        self::assertStringContainsString(
            $errorMessage,
            $failure->getReason()
        );
    }

    private function createIngesterSource(string $path): IngesterSourceInterface
    {
        return (new LocalCsvIngesterSource())->setPath($path);
    }
}
