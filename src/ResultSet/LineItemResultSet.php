<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\ResultSet;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use OAT\SimpleRoster\Model\LineItemCollection;

class LineItemResultSet implements Countable, IteratorAggregate, JsonSerializable
{
    private LineItemCollection $collection;
    private bool $hasMore;
    private ?int $lastLineItemId;

    public function __construct(LineItemCollection $collection, bool $hasMore, ?int $lastLineItemId)
    {
        $this->collection = $collection;
        $this->hasMore = $hasMore;
        $this->lastLineItemId = $lastLineItemId;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function getLastLineItemId(): ?int
    {
        return $this->lastLineItemId;
    }

    public function getLineItemCollection(): LineItemCollection
    {
        return $this->collection;
    }

    public function getIterator(): ArrayIterator
    {
        return $this->collection->getIterator();
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function jsonSerialize(): array
    {
        return $this->collection->jsonSerialize();
    }
}
