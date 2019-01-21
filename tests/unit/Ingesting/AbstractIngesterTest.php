<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\AbstractIngester;
use App\Ingesting\RowToModelMapper\AbstractRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\Model\ModelInterface;
use App\ModelManager\ModelManagerInterface;
use App\Validation\ModelValidator;
use App\Validation\ValidationException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

abstract class AbstractIngesterTest extends TestCase
{
    /**
     * @var ModelManagerInterface|MockObject
     */
    protected $modelManager;

    /**
     * @var AbstractRowToModelMapper|MockObject
     */
    protected $rowToModelMapper;

    /**
     * @var ModelValidator|MockObject
     */
    protected $modelValidator;

    /**
     * @var AbstractIngester|MockObject
     */
    protected $ingester;

    /**
     * @var SourceInterface|MockObject
     */
    protected $source;

    public function setUp()
    {
        $this->source = $this->createMock(SourceInterface::class);
    }

    protected function arrayAsGenerator(array $array): \Generator
    {
        foreach ($array as $item) {
            yield $item;
        }
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testItWorks(array $items)
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($items));
        $this->modelValidator->method('validate')->willReturn(true);

        $this->modelManager->method('read')->willReturn(null);
        $this->modelManager->expects($this->exactly(count($items)))->method('insert');

        $result = $this->ingester->ingest($this->source, false);

        $this->assertEquals($result['rowsAdded'], count($items));
        $this->assertEquals($result['alreadyExistingRowsCount'], 0);
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testDryRun(array $items)
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($items));
        $this->modelValidator->method('validate')->willReturn(true);

        $this->modelManager->expects($this->exactly(count($items)))->method('read');
        $this->modelManager->method('read')->willReturn(null);

        $this->modelManager->expects($this->never())->method('insert');

        $result = $this->ingester->ingest($this->source, true);

        // assert the stats are the same as if it were a non-dry run
        $this->assertEquals($result['rowsAdded'], count($items));
        $this->assertEquals($result['alreadyExistingRowsCount'], 0);
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testItSkipsRecordExistingInStorage(array $items)
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($items));
        $this->modelValidator->method('validate')->willReturn(true);

        // only 1 item to exists
        $this->modelManager->expects($this->at(1))->method('read')->willReturn($this->createMock(ModelInterface::class));
        // all the rest items don't exist
        for ($i = 1; $i < count($items); $i++) {
            $this->modelManager->expects($this->at($i + 1))->method('read')->willReturn(null);
        }

        if ($this->ingester->isUpdateMode()) {
            $this->modelManager->expects($this->exactly(count($items)))->method('insert');
        } else {
            $this->modelManager->expects($this->exactly(count($items) - 1))->method('insert');
        }

        $result = $this->ingester->ingest($this->source, false);

        $this->assertEquals($result['rowsAdded'], count($items) - 1);
        $this->assertEquals($result['alreadyExistingRowsCount'], 1);
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testItBreaksOnFirstInvalidLine(array $items)
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($items));
        $this->modelValidator->method('validate')->willThrowException(new ValidationException());

        $this->expectException(FileLineIsInvalidException::class);

        $this->ingester->ingest($this->source, false);
    }

    public function testItBreaksValidationIfModelConstructorFails()
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator(['']));

        $this->expectException(FileLineIsInvalidException::class);

        $this->ingester->ingest($this->source, false);
    }
}