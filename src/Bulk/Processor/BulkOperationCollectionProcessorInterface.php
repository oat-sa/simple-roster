<?php declare(strict_types=1);

namespace App\Bulk\Processor;

use App\Bulk\Operation\BulkOperationCollection;
use App\Bulk\Result\BulkResult;

interface BulkOperationCollectionProcessorInterface
{
    public function process(BulkOperationCollection $operationCollection): BulkResult;
}
