<?php declare(strict_types=1);

namespace App\Bulk\Operation;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class BulkOperationCollection implements IteratorAggregate, Countable
{
    /** @var BulkOperation[] */
    private $operations = [];

    /** @var bool */
    private $isDryRun;

    public function __construct(bool $isDryRun = false)
    {
        $this->isDryRun = $isDryRun;
    }

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

    public function isDryRun(): bool
    {
        return $this->isDryRun;
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
