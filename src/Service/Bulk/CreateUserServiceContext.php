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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Bulk;

use Generator;

class CreateUserServiceContext
{
    private array $prefix;
    private array $prefixGroup;
    private int $batchSize;
    private bool $recreateQAUsers;

    public function __construct(array $prefix, array $prefixGroup, int $batchSize, bool $recreateQAUsers)
    {
        $this->prefix = $prefix;
        $this->prefixGroup = $prefixGroup;
        $this->batchSize = $batchSize;
        $this->recreateQAUsers = $recreateQAUsers;
    }

    /**
     * @return string[]
     */
    public function getPrefixes(): array
    {
        return $this->prefix;
    }

    /**
     * @return string[]
     */
    public function getPrefixGroup(): array
    {
        return $this->prefixGroup;
    }

    public function getPrefixesCount(): int
    {
        return count($this->prefix) * count($this->prefixGroup) * $this->batchSize;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function withBatch(int $batchSize): self
    {
        return new self(
            $this->prefix,
            $this->prefixGroup,
            $batchSize,
            $this->recreateQAUsers
        );
    }

    public function withPrefixes(array $prefix): self
    {
        return new self(
            $prefix,
            $this->prefixGroup,
            $this->batchSize,
            $this->recreateQAUsers
        );
    }

    public function withRecreateUsers(bool $recreateUsers): self
    {
        return new self(
            $this->prefix,
            $this->prefixGroup,
            $this->batchSize,
            $recreateUsers
        );
    }

    /**
     * @return PrefixesVO[]|Generator
     */
    public function iteratePrefixes(): Generator
    {
        foreach ($this->prefixGroup as $prefixGroup) {
            foreach ($this->prefix as $prefix) {
                yield new PrefixesVO($prefix, $prefixGroup);
            }
        }
    }

    /**
     * @return bool
     */
    public function isRecreateQAUsers(): bool
    {
        return $this->recreateQAUsers;
    }
}
