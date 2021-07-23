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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\ResultSet;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\Model\UsernameCollection;
use Symfony\Component\Uid\UuidV6;

class UsernameResultSet implements Countable, IteratorAggregate
{
    private UsernameCollection $collection;
    private bool $hasMore;
    private ?UuidV6 $lastUserId;

    public function __construct(UsernameCollection $collection, bool $hasMore, ?UuidV6 $lastUserId)
    {
        $this->collection = $collection;
        $this->hasMore = $hasMore;
        $this->lastUserId = $lastUserId;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function getLastUserId(): ?UuidV6
    {
        return $this->lastUserId;
    }

    public function getUsernameCollection(): UsernameCollection
    {
        return $this->collection;
    }

    public function getIterator(): ArrayIterator
    {
        return $this->collection->getIterator();
    }

    public function count(): int
    {
        return count($this->collection);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
