<?php declare(strict_types=1);

namespace App\Bulk\Operation;

use ArrayIterator;
use IteratorAggregate;

class BulkOperationCollection implements IteratorAggregate
{
    /** @var BulkOperation[] */
    private $operations = [];

    public function add(BulkOperation $operation): self
    {
        $this->operations[$operation->getIdentifier()] = $operation;

        return $this;
    }

    /**
     * @return ArrayIterator|BulkOperation[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->operations);
    }
}
