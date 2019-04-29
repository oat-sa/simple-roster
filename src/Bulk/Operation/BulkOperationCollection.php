<?php declare(strict_types=1);

namespace App\Bulk\Operation;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class BulkOperationCollection implements IteratorAggregate, Countable
{
    /** @var BulkOperation[] */
    private $operations = [];


    public function add(BulkOperation $operation): self
    {
        $this->operations[$operation->getIdentifier()] = $operation;

        return $this;
    }

    public function clear(): self
    {
        $this->operations = [];

        return $this;
    }

    public function count()
    {
        return count($this->operations);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return ArrayIterator|BulkOperation[]
     */
    public function getIterator()
    {
        return new ArrayIterator($this->operations);
    }
}
