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

namespace App\ResultSet;

use App\Model\UsernameCollection;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class UsernameResultSet implements Countable, IteratorAggregate
{
    /** @var UsernameCollection */
    private $collection;

    /** @var bool */
    private $hasMore;

    /** @var int|null */
    private $lastUserId;

    public function __construct(UsernameCollection $collection, bool $hasMore, ?int $lastUserId)
    {
        $this->collection = $collection;
        $this->hasMore = $hasMore;
        $this->lastUserId = $lastUserId;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function getLastUserId(): ?int
    {
        return $this->lastUserId;
    }

    public function getIterator(): ArrayIterator
    {
        return $this->collection->getIterator();
    }

    public function count(): int
    {
        return count($this->collection);
    }
}
