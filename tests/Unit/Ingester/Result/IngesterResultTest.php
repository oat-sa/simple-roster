<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Result;

use App\Ingester\Result\IngesterResult;
use App\Ingester\Result\IngesterResultFailure;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class IngesterResultTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $subject = new IngesterResult('ingester', 'source');

        $this->assertEquals('ingester', $subject->getIngesterType());
        $this->assertEquals('source', $subject->getSourceType());
        $this->assertEquals(0, $subject->getSuccessCount());
        $this->assertEmpty($subject->getFailures());
        $this->assertFalse($subject->hasFailures());
        $this->assertTrue($subject->isDryRun());
    }

    public function testItCanAddSuccesses()
    {
        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addSuccess()
            ->addSuccess();

        $this->assertEquals(2, $subject->getSuccessCount());
        $this->assertFalse($subject->hasFailures());
    }

    public function testItCanAddAndRetrieveFailures()
    {
        $failure1 = new IngesterResultFailure(1, ['data'], 'reason1');
        $failure2 = new IngesterResultFailure(2, ['data2'], 'reason2');

        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addFailure($failure1)
            ->addFailure($failure2);

        $this->assertEquals(0, $subject->getSuccessCount());
        $this->assertTrue($subject->hasFailures());
        $this->assertEquals(
            [
                1 => $failure1,
                2 => $failure2
            ],
            $subject->getFailures()
        );
    }

    public function testPostDryRunStringRepresentation()
    {
        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addSuccess()
            ->addSuccess()
            ->addFailure($this->createMock(IngesterResultFailure::class));

        $this->assertEquals(
            "[DRY_RUN] Ingestion (type='ingester', source='source'): 2 successes, 1 failures.",
            $subject->__toString()
        );
    }

    public function testPostRunStringRepresentation()
    {
        $subject = new IngesterResult('ingester', 'source', false);

        $subject
            ->addSuccess()
            ->addSuccess()
            ->addFailure($this->createMock(IngesterResultFailure::class));

        $this->assertEquals(
            "Ingestion (type='ingester', source='source'): 2 successes, 1 failures.",
            $subject->__toString()
        );
    }
}