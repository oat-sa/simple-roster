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

namespace OAT\SimpleRoster\Repository\Criteria;

use Symfony\Component\Uid\UuidV6;

class FindLineItemCriteria
{
    /** @var UuidV6[] */
    private $lineItemIds = [];

    /** @var string[] */
    private array $lineItemSlugs = [];

    /** @var string[] */
    private $lineItemGroupIds = [];

    public function addLineItemIds(UuidV6 ...$lineItemIds): self
    {
        $this->lineItemIds = $lineItemIds;

        return $this;
    }

    public function addLineItemSlugs(string ...$lineItemSlugs): self
    {
        $this->lineItemSlugs = $lineItemSlugs;

        return $this;
    }

    public function addLineItemGroupIds(string ...$lineItemGroupIds): self
    {
        $this->lineItemGroupIds = $lineItemGroupIds;

        return $this;
    }

    /**
     * @return UuidV6[]
     */
    public function getLineItemIds(): array
    {
        return $this->lineItemIds;
    }

    public function getLineItemSlugs(): array
    {
        return $this->lineItemSlugs;
    }

    public function getLineItemGroupIds(): array
    {
        return $this->lineItemGroupIds;
    }

    public function hasLineItemIdsCriteria(): bool
    {
        return !empty($this->lineItemIds);
    }

    public function hasLineItemSlugsCriteria(): bool
    {
        return !empty($this->lineItemSlugs);
    }

    public function hasLineItemGroupIdsCriteria(): bool
    {
        return !empty($this->lineItemGroupIds);
    }
}
