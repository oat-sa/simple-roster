<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Source;

use App\Ingester\Source\LocalCsvIngesterSource;
use PHPUnit\Framework\TestCase;

class LocalCsvIngesterSourceTest extends TestCase
{
    public function testGetContentWithDefaultDelimiter(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv');

        $output = $subject->getContent();

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
        $subject = new LocalCsvIngesterSource();
        $subject
            ->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/infrastructures.csv')
            ->setDelimiter('|');

        $output = $subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(1, $row);
        }
    }

    public function testGetContentWithOtherCharset(): void
    {
        $subject = new LocalCsvIngesterSource();
        $subject
            ->setPath(__DIR__ . '/../../../Resources/Ingester/Valid/UTF-16LE-infrastructures.csv')
            ->setCharset('UTF-16LE');

        $output = $subject->getContent();

        foreach ($output as $row) {
            $this->assertCount(4, $row);
            $this->assertEquals('ms', $row['label']);
            $this->assertEquals('https://itinv01exp.invalsi.taocloud.org', $row['ltiDirectorLink']);
            $this->assertEquals('key', $row['ltiKey']);
            $this->assertEquals('secret', $row['ltiSecret']);
        }
    }
}
