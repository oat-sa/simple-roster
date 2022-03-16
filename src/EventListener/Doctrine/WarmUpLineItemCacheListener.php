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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Repository\LineItemRepository;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class WarmUpLineItemCacheListener implements EntityListenerInterface
{
    private CacheItemPoolInterface $resultCache;
    private LineItemCacheIdGenerator $lineItemCacheIdGenerator;
    private LineItemRepository $lineItemRepository;

    public function __construct(
        LineItemRepository $lineItemRepository,
        EntityManagerInterface $entityManager,
        LineItemCacheIdGenerator $lineItemCacheIdGenerator
    ) {
        $resultCache = $entityManager->getConfiguration()->getResultCache();

        if (!$resultCache instanceof CacheItemPoolInterface) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCache = $resultCache;
        $this->lineItemCacheIdGenerator = $lineItemCacheIdGenerator;
        $this->lineItemRepository = $lineItemRepository;
    }

    /**
     * @throws InvalidArgumentException
     * @throws EntityNotFoundException
     */
    public function postUpdate(LineItem $lineItem): void
    {
        $id = (int)$lineItem->getId();
        $this->resultCache->deleteItem($this->lineItemCacheIdGenerator->generate($id));

        // Refresh the cache by query
        $this->lineItemRepository->findOneById($id);
    }
}
