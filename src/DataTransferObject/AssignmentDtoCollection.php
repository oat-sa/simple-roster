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

namespace OAT\SimpleRoster\DataTransferObject;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class AssignmentDtoCollection implements Countable, IteratorAggregate
{
    /** @var AssignmentDto[] */
    private array $assignments;

    public function __construct(AssignmentDto ...$assignments)
    {
        $this->assignments = $assignments;
    }

    public function add(AssignmentDto $dto): self
    {
        $this->assignments[] = $dto;

        return $this;
    }

    public function clear(): self
    {
        $this->assignments = [];

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAllUsernames(): array
    {
        return array_values(
            array_unique(
                array_map(
                    static function (AssignmentDto $assignmentDto): string {
                        return $assignmentDto->getUsername();
                    },
                    $this->assignments
                )
            )
        );
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return ArrayIterator|AssignmentDto[]
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->assignments);
    }

    public function count(): int
    {
        return count($this->assignments);
    }
}
