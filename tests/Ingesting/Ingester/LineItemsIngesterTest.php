<?php

namespace App\Tests\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\LineItemsIngester;
use App\Ingesting\Source\SourceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LineItemsIngesterTest extends KernelTestCase
{
    public function invalidItemsProvider()
    {
        return [
            'no_uri' => [[['', 'title', 'infrastructure_id']]],
            'no_title' => [[['uri', '', 'infrastructure_id']]],
            'no_infra' => [[['uri', 'title', '']]]
        ];
    }

    /**
     * @dataProvider invalidItemsProvider
     */
    public function testItBreaksOnFirstInvalidLine(array $row)
    {
        $kernel = static::bootKernel();

        $testContainer = $kernel->getContainer()->get('test.service_container');

        $ingester = $testContainer->get(LineItemsIngester::class);

        $source = $this->createMock(SourceInterface::class);
        $source->method('iterateThroughLines')->willReturn($row);

        $this->expectException(FileLineIsInvalidException::class);

        $ingester->ingest($source, true);
    }
}