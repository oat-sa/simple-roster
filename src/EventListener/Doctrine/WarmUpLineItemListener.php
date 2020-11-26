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

namespace OAT\SimpleRoster\EventListener\Doctrine;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;

class WarmUpLineItemListener implements EntityListenerInterface
{
    /** @var CacheProvider */
    private $cacheProvider;

    /** @var LineItemCacheIdGenerator */
    private $lineItemCacheIdGenerator;

    /** @var LineItemRepository */
    private $lineItemRepository;

    public function __construct(
        LineItemRepository $lineItemRepository,
        EntityManagerInterface $entityManager,
        LineItemCacheIdGenerator $lineItemCacheIdGenerator
    ) {
        $this->cacheProvider = $entityManager->getConfiguration()->getResultCacheImpl();
        $this->lineItemCacheIdGenerator = $lineItemCacheIdGenerator;
        $this->lineItemRepository = $lineItemRepository;
    }

    public function postUpdate(LineItem $lineItem): void
    {
        $this->cacheProvider->delete($this->lineItemCacheIdGenerator->generate($lineItem->getId()));
        $this->lineItemRepository->findById($lineItem->getId());
    }
}