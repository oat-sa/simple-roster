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

namespace OAT\SimpleRoster\Repository\Criteria;

class LtiInstanceCriteria
{
    /** @var string[] */
    private array $labels = [];

    /** @var string[] */
    private array $links = [];

    public function addLtiLabels(string ...$labels): self
    {
        $this->labels = $labels;

        return $this;
    }

    public function hasLtiLabels(): bool
    {
        return !empty($this->labels);
    }

    /**
     * @return string[]
     */
    public function getLtiLabels(): array
    {
        return $this->labels;
    }

    public function addLtiLinks(string ...$links): self
    {
        $this->links = $links;

        return $this;
    }

    public function hasLtiLinks(): bool
    {
        return !empty($this->links);
    }

    /**
     * @return string[]
     */
    public function getLtiLinks(): array
    {
        return $this->links;
    }
}