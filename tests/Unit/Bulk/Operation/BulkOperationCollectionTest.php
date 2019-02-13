<?php declare(strict_types=1);

namespace App\Tests\Unit\Bulk\Operation;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Operation\BulkOperationCollection;
use PHPUnit\Framework\TestCase;

class BulkOperationCollectionTest extends TestCase
{
    public function testItCanAddAnRetrieveBulkOperations(): void
    {
        $subject = new BulkOperationCollection();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE, ['key' => 'value']);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE, ['key2' => 'value2']);

        $subject
            ->add($operation1)
            ->add($operation2);

        $this->assertCount(2, $subject);
        $this->assertEquals(
            [
                'identifier1' => $operation1,
                'identifier2' => $operation2,
            ],
            $subject->getIterator()->getArrayCopy()
        );
    }
}
