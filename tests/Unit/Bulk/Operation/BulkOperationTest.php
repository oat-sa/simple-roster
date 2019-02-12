<?php declare(strict_types=1);

namespace App\Tests\Unit\Bulk\Operation;

use App\Bulk\Operation\BulkOperation;
use PHPUnit\Framework\TestCase;

class BulkOperationTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new BulkOperation('identifier', BulkOperation::TYPE_UPDATE, ['key' => 'value']);

        $this->assertEquals('identifier', $subject->getIdentifier());
        $this->assertEquals(BulkOperation::TYPE_UPDATE, $subject->getType());
        $this->assertEquals(['key' => 'value'], $subject->getAttributes());
        $this->assertEquals('value', $subject->getAttribute('key'));
    }
}
