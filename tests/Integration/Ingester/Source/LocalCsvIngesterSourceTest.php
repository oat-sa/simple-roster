<?php declare(strict_types=1);

namespace App\Tests\Integration\Ingester\Ingester;

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
            $this->assertContains('infra', $row['label']);
            $this->assertContains('http://infra', $row['ltiDirectorLink']);
            $this->assertContains('key', $row['ltiKey']);
            $this->assertContains('secret', $row['ltiSecret']);
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
}
