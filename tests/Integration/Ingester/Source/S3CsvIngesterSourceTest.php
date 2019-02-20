<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

use App\Ingester\Source\S3CsvIngesterSource;
use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;

class S3CsvIngesterSourceTest extends TestCase
{
    /** @var S3CsvIngesterSource */
    private $subject;

    /** @var S3Client */
    private $client;

    protected function setUp()
    {
        parent::setUp();

        $this->client = $this->createMock(S3Client::class);
        $this->subject = new S3CsvIngesterSource($this->client, 'bucket');
        $this->subject->setPath('path');
    }

    public function testGetContentWithDefaultDelimiter(): void
    {
        $this->prepareS3Client();

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(4, $row);
            $this->assertContains('infra', $row['label']);
            $this->assertContains('http://infra', $row['ltiDirectorLink']);
            $this->assertContains('key', $row['ltiKey']);
            $this->assertContains('secret', $row['ltiSecret']);
        }
    }

    public function testGetContentWithOtherDelimiter(): void
    {
        $this->prepareS3Client();

        $this->subject->setDelimiter('|');

        $output = $this->subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(1, $row);
        }
    }

    private function prepareS3Client(): void
    {
        $this->client
            ->expects($this->once())
            ->method('__call')
            ->with('getObject', [[
                'Bucket' => 'bucket',
                'Key' => 'path'
            ]])
            ->willReturn([
                'Body' => file_get_contents(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv')
            ]);
    }
}
