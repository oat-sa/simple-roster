<?php

namespace App\Tests\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\InfrastructuresIngester;
use App\Ingesting\Source\SourceInterface;
use App\Tests\GeneratorHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InfrastructuresIngesterTest extends KernelTestCase
{
    use GeneratorHelperTrait;

    public function invalidItemsProvider()
    {
        return [
            'no_id' => [[['', 'lti_director_link', 'key', 'secret']]],
            'no_lti' => [[['id', '', 'key', 'secret']]],
            'no_key' => [[['id', 'lti_director_link', '', 'secret']]],
            'no_secret' => [[['id', 'lti_director_link', 'key', '']]]
        ];
    }

    /**
     * @dataProvider invalidItemsProvider
     */
    public function testItBreaksOnFirstInvalidLine(array $row)
    {
        $kernel = static::bootKernel();

        $testContainer = $kernel->getContainer()->get('test.service_container');

        $ingester = $testContainer->get(InfrastructuresIngester::class);

        $source = $this->createMock(SourceInterface::class);
        $source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($row));

        $this->expectException(FileLineIsInvalidException::class);

        $ingester->ingest($source, true);
    }
}