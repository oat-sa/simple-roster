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

namespace App\DataTransferObject;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class UserDtoCollection implements Countable, IteratorAggregate
{
    /** @var UserDto[] */
    private $collection = [];

    public function add(UserDto $dto): self
    {
        $this->collection[] = $dto;

        return $this;
    }

    public function clear(): self
    {
        $this->collection = [];

        return $this;
    }

    /**
     * @return ArrayIterator|UserDto[]
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collection);
    }

    public function count()
    {
        return count($this->collection);
    }

    public function isEmpty(): bool
    {
        return count($this) === 0;
    }
}