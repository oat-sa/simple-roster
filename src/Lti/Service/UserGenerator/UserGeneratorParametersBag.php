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

namespace OAT\SimpleRoster\Lti\Service\UserGenerator;

class UserGeneratorParametersBag
{
    private string $groupPrefix;
    /** @var string[] $prefixes */
    private array $prefixes;
    private int $batchSize;

    /**
     * @param string[] $prefixes
     */
    public function __construct(
        string $groupPrefix,
        array $prefixes,
        int $batchSize
    ) {
        $this->groupPrefix = $groupPrefix;
        $this->prefixes = $prefixes;
        $this->batchSize = $batchSize;
    }

    /**
     * @return string
     */
    public function getGroupPrefix(): string
    {
        return $this->groupPrefix;
    }

    /**
     * @return string[]
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * @return int
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }
}
