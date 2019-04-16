<?php declare(strict_types=1);

namespace App\Bulk\Result;

use App\Bulk\Operation\BulkOperation;
use JsonSerializable;

class BulkResult implements JsonSerializable
{
    /** @var bool[] */
    private $results = [];

    /** @var int */
    private $failuresCount = 0;

    public function addBulkOperationSuccess(BulkOperation $operation): self
    {
        return $this->addBulkOperationResult($operation, true);
    }

    public function addBulkOperationFailure(BulkOperation $operation): self
    {
        $this->failuresCount++;

        return $this->addBulkOperationResult($operation, false);
    }

    public function hasFailures(): bool
    {
        return $this->failuresCount > 0;
    }

    public function jsonSerialize()
    {
        return [
            'data' => [
                'applied' => !$this->hasFailures(),
                'results' => $this->results,
            ],
        ];
    }

    private function addBulkOperationResult(BulkOperation $operation, bool $result): self
    {
        $this->results[$operation->getIdentifier()] = $result;

        return $this;
    }
}
