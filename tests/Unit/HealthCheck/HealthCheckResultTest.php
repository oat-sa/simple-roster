<?php declare(strict_types=1);

namespace App\Tests\Unit\HealthCheck;

use App\HealthCheck\HealthCheckResult;
use PHPUnit\Framework\TestCase;

class HealthCheckResultTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new HealthCheckResult(true, false);

        $this->assertTrue($subject->isDoctrineConnectionAvailable());
        $this->assertFalse($subject->isDoctrineCacheAvailable());
    }

    public function testJsonSerialization(): void
    {
        $subject = new HealthCheckResult(false, true);

        $this->assertEquals(
            [
                'isDoctrineConnectionAvailable' => false,
                'isDoctrineCacheAvailable' => true,
            ],
            $subject->jsonSerialize()
        );
    }
}
