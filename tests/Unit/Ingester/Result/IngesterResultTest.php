<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Result;

use App\Ingester\Result\IngesterResult;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class IngesterResultTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $subject = new IngesterResult('ingester', 'source');

        $this->assertEquals('ingester', $subject->getIngesterType());
        $this->assertEquals('source', $subject->getSourceType());
        $this->assertEmpty($subject->getSuccesses());
        $this->assertEmpty($subject->getFailures());
        $this->assertTrue($subject->isDryRun());
    }

    public function testPostDryRunStringRepresentation()
    {
        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addSuccess([])
            ->addSuccess([])
            ->addFailure([]);

        $this->assertEquals(
            "[DRY_RUN] Ingestion (type='ingester', source='source'): 2 successes, 1 failures.",
            $subject->__toString()
        );
    }

    public function testPostRunStringRepresentation()
    {
        $subject = new IngesterResult('ingester', 'source', false);

        $subject
            ->addSuccess([])
            ->addSuccess([])
            ->addFailure([]);

        $this->assertEquals(
            "Ingestion (type='ingester', source='source'): 2 successes, 1 failures.",
            $subject->__toString()
        );
    }
}