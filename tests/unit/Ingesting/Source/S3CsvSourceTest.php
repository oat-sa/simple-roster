<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Source\S3CsvSource;
use App\S3\S3ClientInterface;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class S3CsvSourceTest extends TestCase
{
    public function testItWorks()
    {
        $s3Client = $this->createMock(S3ClientInterface::class);
        $s3Client->method('getObject')->willReturn(<<<CSV
"field1", "field2","field3" , "field 4"
"field1", 
"field1", "field2", "field3", "field 4","field5"
CSV
);

        $source = new S3CsvSource($s3Client, 'bucket', 'object', ',');

        $result = [];
        foreach ($source->iterateThroughLines() as $line) {
            $result[] = $line;
        }

        $this->assertEquals(count($result), 3);
        $this->assertEquals($result[2][4], 'field5');
    }
}