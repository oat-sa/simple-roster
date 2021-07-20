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

use DateTimeInterface;

class FindLineItemCriteria
{
    /** @var int[] */
    private array $lineItemIds = [];

    /** @var string[] */
    private array $lineItemSlugs = [];

    /** @var string[] */
    private array $lineItemLabels = [];

    /** @var string[] */
    private array $lineItemUris = [];

    private DateTimeInterface $lineItemStartAt;
    private DateTimeInterface $lineItemEndtAt;

    public function addLineItemIds(int ...$lineItemIds): self
    {
        $this->lineItemIds = $lineItemIds;

        return $this;
    }

    public function addLineItemSlugs(string ...$lineItemSlugs): self
    {
        $this->lineItemSlugs = $lineItemSlugs;

        return $this;
    }

    public function addLineItemLabels(string ...$lineItemLabels): self
    {
        $this->lineItemLabels = $lineItemLabels;

        return $this;
    }

    public function addLineItemUris(string ...$lineItemUris): self
    {
        $this->lineItemUris = $lineItemUris;

        return $this;
    }

    public function addLineItemStartAt(DateTimeInterface $lineItemStartAt): self
    {
        $this->lineItemStartAt = $lineItemStartAt;

        return $this;
    }

    public function addLineItemEndAt(DateTimeInterface $lineItemEndAt): self
    {
        $this->lineItemEndtAt = $lineItemEndAt;

        return $this;
    }

    public function getLineItemIds(): array
    {
        return $this->lineItemIds;
    }

    public function getLineItemSlugs(): array
    {
        return $this->lineItemSlugs;
    }

    public function getLineItemLabels(): array
    {
        return $this->lineItemLabels;
    }

    public function getLineItemUris(): array
    {
        return $this->lineItemUris;
    }

    public function getLineItemStartAt(): DateTimeInterface
    {
        return $this->lineItemStartAt;
    }

    public function getLineItemEndAt(): DateTimeInterface
    {
        return $this->lineItemEndtAt;
    }

    public function hasLineItemIdsCriteria(): bool
    {
        return !empty($this->lineItemIds);
    }

    public function hasLineItemSlugsCriteria(): bool
    {
        return !empty($this->lineItemSlugs);
    }

    public function hasLineItemLabelsCriteria(): bool
    {
        return !empty($this->lineItemLabels);
    }

    public function hasLineItemUrisCriteria(): bool
    {
        return !empty($this->lineItemUris);
    }

    public function hasLineItemStartAt(): bool
    {
        return !empty($this->lineItemStartAt);
    }

    public function hasLineItemEndAt(): bool
    {
        return !empty($this->lineItemEndAt);
    }
}
