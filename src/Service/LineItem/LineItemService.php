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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\LineItem;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;

class LineItemService
{
    private LineItemRepository $lineItemRepository;

    public function __construct(LineItemRepository $lineItemRepository)
    {
        $this->lineItemRepository = $lineItemRepository;
    }

    public function listLineItems(
        FindLineItemCriteria $findLineItemCriteria,
        int $limit,
        int $cursor = null
    ): ListLineItemResponse {
        $lineItemResultSet = $this->lineItemRepository->findLineItemsByCriteria(
            $findLineItemCriteria,
            $limit,
            $cursor
        );

        return new ListLineItemResponse($lineItemResultSet);
    }

    public function createOrUpdateLineItem(LineItem $lineItem): LineItem
    {
        return $this->lineItemRepository->createOrUpdate($lineItem);
    }
}
