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
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;

class WarmUpLineItemCacheListener implements EntityListenerInterface
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
        $cacheProvider = $entityManager->getConfiguration()->getResultCacheImpl();

        if (!$cacheProvider instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->cacheProvider = $cacheProvider;
        $this->lineItemCacheIdGenerator = $lineItemCacheIdGenerator;
        $this->lineItemRepository = $lineItemRepository;
    }

    public function postUpdate(LineItem $lineItem): void
    {
        $id = (int)$lineItem->getId();
        $this->cacheProvider->delete($this->lineItemCacheIdGenerator->generate($id));

        // Refresh the cache by query
        $this->lineItemRepository->findById($id);
    }
}
