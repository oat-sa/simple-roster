<?php declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CommandWatcherTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class CommandWatcherTraitTest extends TestCase
{
    use CommandWatcherTrait;

    protected function setUp()
    {
        parent::setUp();
    }

    public function testInInstantiatesAStopWatchIfItDoesNotExist(): void
    {
        $this->assertNull($this->watcher);

        $watcher = $this->startWatch('testName', 'testCategory');

        $this->assertInstanceOf(Stopwatch::class, $this->watcher);
        $this->assertEquals($this->watcher, $watcher);
    }

    public function testItReturnFormattedOutput(): void
    {
        $stopWatchEvent = $this->createMock(StopwatchEvent::class);
        $stopWatchEvent
            ->method('getMemory')
            ->willReturn(1024 ** 5 + 1);

        $stopWatchEvent
            ->method('getDuration')
            ->willReturn(3600 * 3600 + 5);

        $this->watcher = $this->createMock(Stopwatch::class);
        $this->watcher
            ->method('stop')
            ->willReturn($stopWatchEvent);

        $this->startWatch('testName', 'testCategory');

        $this->assertEquals(
            'memory: 1024TB - duration: 3h 35m 59s 05ms',
            $this->stopWatch('testName')
        );
    }

    public function testIt(): void
    {
        $stopWatchEvent = $this->createMock(StopwatchEvent::class);
        $stopWatchEvent
            ->method('getMemory')
            ->willReturn(-1);

        $stopWatchEvent
            ->method('getDuration')
            ->willReturn(3615);

        $this->watcher = $this->createMock(Stopwatch::class);
        $this->watcher
            ->method('stop')
            ->willReturn($stopWatchEvent);

        $this->startWatch('testName', 'testCategory');

        $this->assertEquals(
            'memory: 0B - duration: 2s 615ms',
            $this->stopWatch('testName')
        );
    }
}
