<?php declare(strict_types=1);

namespace App\Tests\Unit\Bulk\Result;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Result\BulkResult;
use PHPUnit\Framework\TestCase;

class BulkResultTest extends TestCase
{
    public function testJsonSerializationWithBulkOperationSuccesses(): void
    {
        $subject = new BulkResult();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE, ['key' => 'value']);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE, ['key2' => 'value2']);

        $subject
            ->addBulkOperationSuccess($operation1)
            ->addBulkOperationSuccess($operation2);

        $this->assertEquals(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        'identifier1' => true,
                        'identifier2' => true,
                    ]
                ]
            ],
            $subject->jsonSerialize()
        );
    }

    public function testJsonSerializationWithBulkOperationFailures(): void
    {
        $subject = new BulkResult();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE, ['key' => 'value']);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE, ['key2' => 'value2']);

        $subject
            ->addBulkOperationSuccess($operation1)
            ->addBulkOperationFailure($operation2);

        $this->assertEquals(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        'identifier1' => true,
                        'identifier2' => false,
                    ]
                ]
            ],
            $subject->jsonSerialize()
        );
    }
}
