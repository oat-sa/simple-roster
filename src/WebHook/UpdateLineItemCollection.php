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

use Exception;
use ArrayIterator;
use Closure;
use Countable;
use Iterator;
use IteratorAggregate;

class UpdateLineItemCollection implements IteratorAggregate, Countable
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

        return new self(...$results);
    }

    /**
     * @throws Exception
     */
    public function findLastByTriggeredTimeOrFail(): UpdateLineItemDto
    {
        if ($this->count() === 0) {
            throw new Exception("Fail to find dto. Collection is null");
        }

        /** @var UpdateLineItemDto $dto */
        foreach ($this as $dto) {
            $timestampDto = $dto->getTriggeredTime()->getTimestamp();
            if (!isset($result) || $timestampDto > $result->getTriggeredTime()->getTimestamp()) {
                $result = $dto;
            }
        }

        /** @noinspection PhpUndefinedVariableInspection */
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

    public function count(): int
    {
        return count($this->updateLineItemsDto);
    }
}
