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

use Exception;

class FindUserCriteria
{
    /** @var string[] */
    private array $usernames = [];

    /** @var string[] */
    private array $lineItemSlugs = [];

    private ?EuclideanDivisionCriterion $euclideanDivisionCriterion = null;

    public function addUsernameCriterion(string ...$usernames): self
    {
        $this->usernames = $usernames;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getUsernameCriterion(): array
    {
        return $this->usernames;
    }

    public function hasUsernameCriterion(): bool
    {
        return !empty($this->usernames);
    }

    public function addLineItemSlugCriterion(string ...$lineItemSlugs): self
    {
        $this->lineItemSlugs = $lineItemSlugs;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLineItemSlugCriterion(): array
    {
        return $this->lineItemSlugs;
    }

    public function hasLineItemSlugCriterion(): bool
    {
        return !empty($this->lineItemSlugs);
    }

    public function addEuclideanDivisionCriterion(EuclideanDivisionCriterion $criterion): self
    {
        $this->euclideanDivisionCriterion = $criterion;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getEuclideanDivisionCriterion(): EuclideanDivisionCriterion
    {
        if (null === $this->euclideanDivisionCriterion) {
            throw new Exception('Criterion is not defined.');
        }

        return $this->euclideanDivisionCriterion;
    }

    public function hasEuclideanDivisionCriterion(): bool
    {
        return null !== $this->euclideanDivisionCriterion;
    }
}
