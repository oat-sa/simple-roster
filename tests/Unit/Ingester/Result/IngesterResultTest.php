<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Result;

use App\Ingester\Result\IngesterResult;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class IngesterResultTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $subject = new IngesterResult('type', 10, false);

        $this->assertEquals('type', $subject->getType());
        $this->assertEquals(10, $subject->getRowCount());
        $this->assertFalse($subject->isDryRun());
    }

    public function testStringRepresentation()
    {
        $subject1 = new IngesterResult('type1', 10);

        $this->assertEquals(
            '[DRY_RUN] 10 elements of type type1 have been ingested.',
            $subject1->__toString()
        );

        $subject2 = new IngesterResult('type2', 20, false);

        $this->assertEquals(
            '20 elements of type type2 have been ingested.',
            $subject2->__toString()
        );
    }
}