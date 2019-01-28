<?php

namespace App\Tests\Unit\Ingesting;

use App\Ingesting\Exception\FileLineIsInvalidException;
use App\Ingesting\Ingester\AbstractIngester;
use App\Ingesting\RowToModelMapper\AbstractRowToModelMapper;
use App\Ingesting\Source\SourceInterface;
use App\ODM\ItemManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

abstract class AbstractIngesterTest extends TestCase
{
    /**
     * @var ItemManagerInterface|MockObject
     */
    protected $itemManager;

    /**
     * @var AbstractRowToModelMapper|MockObject
     */
    protected $rowToModelMapper;

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
        $this->itemManager = $this->createMock(ItemManagerInterface::class);
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

        $this->itemManager->method('load')->willReturn(null);
        $this->itemManager->expects($this->exactly(count($items)))->method('save');

        $result = $this->ingester->ingest($this->source, true);

        $this->assertEquals($result['rowsAdded'], count($items));
        $this->assertEquals($result['alreadyExistingRowsCount'], 0);
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testDryRun(array $items)
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator($items));

        $this->itemManager->expects($this->exactly(count($items)))->method('isExist');
        $this->itemManager->method('isExist')->willReturn(false);

        $this->itemManager->expects($this->never())->method('save');

        $result = $this->ingester->ingest($this->source, false);

        // assert the stats are the same as if it were a non-dry run
        $this->assertEquals($result['rowsAdded'], count($items));
        $this->assertEquals($result['alreadyExistingRowsCount'], 0);
    }

    /**
     * @dataProvider itemsProvider
     */
    public function testItSkipsRecordExistingInStorage(array $items)
    {
        $this->source
            ->method('iterateThroughLines')
            ->willReturn($this->arrayAsGenerator($items));

        $this->itemManager
            ->expects($this->exactly(count($items)))
            ->method('isExist')
            ->willReturn(true, false, false);

        if ($this->ingester->isUpdateMode()) {
            $this->itemManager
                ->expects($this->exactly(count($items)))
                ->method('save');
        } else {
            $this->itemManager
                ->expects($this->exactly(count($items) - 1))
                ->method('save');
        }

        $result = $this->ingester->ingest($this->source, true);

        $this->assertEquals($result['rowsAdded'], count($items) - 1);
        $this->assertEquals($result['alreadyExistingRowsCount'], 1);
    }

    public function testItBreaksValidationIfModelConstructorFails()
    {
        $this->source->method('iterateThroughLines')->willReturn($this->arrayAsGenerator(['']));

        $this->expectException(FileLineIsInvalidException::class);

        $this->ingester->ingest($this->source, true);
    }
}