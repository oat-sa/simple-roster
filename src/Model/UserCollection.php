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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Model;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use OAT\SimpleRoster\Entity\User;

class UserCollection implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var User[] */
    private array $collection;

    /**
     * @param User[] $data
     */
    public function __construct(array $data = [])
    {
        $this->collection = $data;
    }

    public function add(User $lineItem): self
    {
        $this->collection[] = $lineItem;

        return $this;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collection);
    }

    public function count(): int
    {
        return count($this->collection);
    }

    public function isEmpty(): bool
    {
        return count($this->collection) === 0;
    }

    public function jsonSerialize(): array
    {
        return array_values($this->collection);
    }
}
