<?php

namespace App\Tests\Ingesting\Ingester;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\UserAndAssignmentsIngester;
use App\Ingesting\Source\SourceInterface;
use App\Tests\GeneratorHelperTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserAndAssignmentsIngesterTest extends KernelTestCase
{
    use GeneratorHelperTrait;

    public function invalidItemsProvider()
    {
        return [
            'no_username' => [[['', 'pass', 'assignment_1']]],
            'no_pass' => [[['username', '', 'assignment_1']]],
            'no_assignment' => [[['username', 'pass', '']]],
        ];
    }

    /**
     * @dataProvider invalidItemsProvider
     */
    public function testItBreaksOnFirstInvalidLine(array $row)
    {
        $kernel = static::bootKernel();

        $testContainer = $kernel->getContainer()->get('test.service_container');

        $ingester = $testContainer->get(UserAndAssignmentsIngester::class);

        $source = $this->createMock(SourceInterface::class);
        $source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($row));

        $this->expectException(FileLineIsInvalidException::class);

        $ingester->ingest($source, true);
    }
}