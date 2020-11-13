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

namespace OAT\SimpleRoster\Tests\Integration\Ingester\Source;

use OAT\SimpleRoster\Ingester\Source\S3CsvIngesterSource;
use Aws\S3\S3Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class S3CsvIngesterSourceTest extends TestCase
{
    /** @var S3CsvIngesterSource */
    private $subject;

    /** @var S3Client|MockObject */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(S3Client::class);
        $this->subject = new S3CsvIngesterSource($this->client, 'bucket');
        $this->subject->setPath('path');
    }

    public function testGetContentWithDefaultDelimiter(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            self::assertCount(4, $row);
            self::assertStringContainsString('infra', $row['label']);
            self::assertStringContainsString('http://infra', $row['ltiLink']);
            self::assertStringContainsString('key', $row['ltiKey']);
            self::assertStringContainsString('secret', $row['ltiSecret']);
        }
    }

    public function testGetContentWithOtherDelimiter(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv');

        $this->subject->setDelimiter('|');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            self::assertCount(1, $row);
        }
    }

    public function testGetContentWithOtherCharset(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/UTF-16LE-lti-instances.csv');

        $this->subject->setCharset('UTF-16LE');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            self::assertCount(4, $row);
            self::assertSame('ms', $row['label']);
            self::assertSame('https://itinv01exp.invalsi.taocloud.org', $row['ltiLink']);
            self::assertSame('key', $row['ltiKey']);
            self::assertSame('secret', $row['ltiSecret']);
        }
    }

    public function testContentIsCountable(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv');

        $this->subject->setDelimiter('|');

        self::assertCount(3, $this->subject);
    }

    public function testItDoesFetchContentFromS3OnlyOnce(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/lti-instances.csv');

        $this->subject->getContent();

        // Retrieving it a second time should result in using class property cache.
        $output = $this->subject->getContent();

        foreach ($output as $row) {
            self::assertCount(4, $row);
            self::assertStringContainsString('infra', $row['label']);
            self::assertStringContainsString('http://infra', $row['ltiLink']);
            self::assertStringContainsString('key', $row['ltiKey']);
            self::assertStringContainsString('secret', $row['ltiSecret']);
        }
    }

    private function prepareS3Client(string $source): void
    {
        $this->client
            ->expects(self::once())
            ->method('__call')
            ->with('getObject', [[
                'Bucket' => 'bucket',
                'Key' => 'path'
            ]])
            ->willReturn([
                'Body' => file_get_contents($source)
            ]);
    }
}
