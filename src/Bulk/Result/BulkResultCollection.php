<?php declare(strict_types=1);

namespace App\Bulk\Result;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class BulkResultCollection implements IteratorAggregate, Countable
{
    /** @var BulkResult[] */
    private $bulkResults = [];

    public function add(BulkResult $bulkResult): self
    {
        $this->bulkResults[] = $bulkResult;

        return $this;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->bulkResults);
    }

    public function count()
    {
        return count($this->bulkResults);
    }
}
