<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */
declare(strict_types=1);

namespace OAT\SimpleRoster\WebHook;

use ArrayIterator;
use Closure;
use Iterator;
use IteratorAggregate;

class UpdateLineItemCollection implements IteratorAggregate
{
    /** @var UpdateLineItemDto[] */
    private $updateLineItemsDto;

    public function __construct(UpdateLineItemDto ...$updateLineItemsDto)
    {
        $this->updateLineItemsDto = $updateLineItemsDto;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->updateLineItemsDto);
    }

    public function map(Closure $callback): Iterator
    {
        $results = [];

        /** @var UpdateLineItemDto $dto */
        foreach ($this as $dto) {
            $results[] = $callback($dto);
        }

        return new ArrayIterator($results);
    }

    public function filter(Closure $callback): self
    {
        $results = [];

        /** @var UpdateLineItemDto $dto */
        foreach ($this as $dto) {
            if ($callback($dto)) {
                $results[] = $dto;
            }
        }

        return new static(...$results);
    }

    public function findLastByTriggeredTime(): ?UpdateLineItemDto
    {
        /** @var null|UpdateLineItemDto $result */
        $result = null;

        /** @var UpdateLineItemDto $dto */
        foreach ($this as $dto) {
            $timestampDto = $dto->getTriggeredTime()->getTimestamp();
            if (null === $result || $timestampDto > $result->getTriggeredTime()->getTimestamp()) {
                $result = $dto;
            }
        }

        return $result;
    }

    public function setStatus(string $status): self
    {
        /** @var UpdateLineItemDto $dto */
        foreach ($this as $dto) {
            $dto->setStatus($status);
        }

        return $this;
    }
}
