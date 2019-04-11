<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Source;

use App\Ingester\Source\S3CsvIngesterSource;
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
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(4, $row);
            $this->assertStringContainsString('infra', $row['label']);
            $this->assertStringContainsString('http://infra', $row['ltiDirectorLink']);
            $this->assertStringContainsString('key', $row['ltiKey']);
            $this->assertStringContainsString('secret', $row['ltiSecret']);
        }
    }

    public function testGetContentWithOtherDelimiter(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $this->subject->setDelimiter('|');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(1, $row);
        }
    }

    public function testGetContentWithOtherCharset(): void
    {
        $this->prepareS3Client(__DIR__ . '/../../../Resources/Ingester/Valid/UTF-16LE-infrastructures.csv');

        $this->subject->setCharset('UTF-16LE');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(4, $row);
            $this->assertEquals('ms', $row['label']);
            $this->assertEquals('https://itinv01exp.invalsi.taocloud.org', $row['ltiDirectorLink']);
            $this->assertEquals('key', $row['ltiKey']);
            $this->assertEquals('secret', $row['ltiSecret']);
        }
    }

    private function prepareS3Client(string $source): void
    {
        $this->client
            ->expects($this->once())
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
