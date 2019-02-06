<?php declare(strict_types=1);

namespace App\Tests\Unit\Ingester\Result;

use App\Ingester\Result\IngesterResultFailure;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class IngesterResultFailureTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $subject = new IngesterResultFailure(10, ['data'], 'reason');

        $this->assertEquals(10, $subject->getLineNumber());
        $this->assertEquals(['data'], $subject->getData());
        $this->assertEquals('reason', $subject->getReason());
    }
}