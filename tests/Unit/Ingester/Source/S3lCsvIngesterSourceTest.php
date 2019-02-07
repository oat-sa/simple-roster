<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Source;

use App\Ingester\Source\S3CsvIngesterSource;
use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class S3CsvIngesterSourceTest extends TestCase
{
    public function testRegistryItemName()
    {
        $subject = new S3CsvIngesterSource(
            $this->createMock(S3Client::class),
            'bucket'
        );

        $this->assertEquals('s3', $subject->getRegistryItemName());
    }
}
