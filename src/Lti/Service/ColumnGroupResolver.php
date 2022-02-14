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

namespace OAT\SimpleRoster\Lti\Service;

use RuntimeException;

/**
 * Returns first group_id until limit is reached then starts return second with the same strategy.
 * */
final class ColumnGroupResolver implements GroupResolverInterface
{
    /** @var string[] */
    private array $idList;
    private int $chunkSize;
    /** @var int[] */
    private array $sizeMap;
    private int $cursor = 0;

    /**
     * @param string[] $idList
     */
    public function __construct(array $idList, int $chunkSize)
    {
        $this->idList = $idList;
        $this->chunkSize = $chunkSize;

        if ($chunkSize < 1) {
            throw new RuntimeException('Chunk size cannot be less then 1.');
        }

        $this->sizeMap = array_fill(0, count($this->idList), 0);
    }

    public function resolve(): string
    {
        if ($this->cursor >= count($this->idList)) {
            throw new RuntimeException('Group ids limit is reached. Cannot resolve group id anymore.');
        }

        $res = $this->idList[$this->cursor];
        $this->sizeMap[$this->cursor]++;

        if ($this->sizeMap[$this->cursor] >= $this->chunkSize) {
            $this->cursor++;
        }

        return $res;
    }
}
