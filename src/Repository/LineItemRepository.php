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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Repository;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\ResultSet\LineItemResultSet;

class LineItemRepository extends AbstractRepository
{
    public const MAX_LINE_ITEM_LIMIT = 100;

    private ManagerRegistry $registry;
    private int $lineItemCacheTtl;
    private LineItemCacheIdGenerator $cacheIdGenerator;

    public function __construct(
        ManagerRegistry $registry,
        LineItemCacheIdGenerator $cacheIdGenerator,
        int $lineItemCacheTtl
    ) {
        parent::__construct($registry, LineItem::class);
        $this->registry = $registry;
        $this->cacheIdGenerator = $cacheIdGenerator;
        $this->lineItemCacheTtl = $lineItemCacheTtl;
    }

    public function findAllAsCollection(): LineItemCollection
    {
        $collection = new LineItemCollection();
        /** @var LineItem $lineItem */
        foreach ($this->findAll() as $lineItem) {
            $collection->add($lineItem);
        }

        return $collection;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findOneById(int $id): LineItem
    {
        $lineItem = $this->createQueryBuilder('l')
            ->select('l')
            ->where('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->enableResultCache($this->lineItemCacheTtl, $this->cacheIdGenerator->generate($id))
            ->getOneOrNullResult();

        if (null === $lineItem) {
            throw new EntityNotFoundException(sprintf("LineItem with id = '%d' cannot be found.", $id));
        }

        return $lineItem;
    }

    public function findLineItemsByCriteria(
        FindLineItemCriteria $criteria,
        int $limit = self::MAX_LINE_ITEM_LIMIT,
        ?int $lastLineItemId = null
    ): LineItemResultSet {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('l')
            ->setMaxResults($limit + 1);

        if ($lastLineItemId !== null) {
            $queryBuilder
                ->andWhere('l.id > :lastLineItemId')
                ->setParameter('lastLineItemId', $lastLineItemId);
        }

        $this->applyCriterias($criteria, $queryBuilder);

        $lineItemIds = [];
        $lineItemsCollection = new LineItemCollection();
        $result = $queryBuilder->getQuery()->getResult();

        /** @var LineItem $lineItem */
        foreach ($result as $lineItem) {
            if (count($lineItemsCollection) < $limit) {
                $lineItemsCollection->add($lineItem);
                $lineItemIds[] = $lineItem->getId();
            }
        }

        return new LineItemResultSet(
            $lineItemsCollection,
            count($result) === $limit + 1,
            $lineItemIds[$limit - 1] ?? null
        );
    }

    private function applyCriterias(FindLineItemCriteria $criteria, QueryBuilder $queryBuilder): void
    {
        if ($criteria->hasLineItemIdsCriteria()) {
            $queryBuilder
                ->andWhere('l.id IN (:ids)')
                ->setParameter('ids', $criteria->getLineItemIds());
        }

        if ($criteria->hasLineItemSlugsCriteria()) {
            $queryBuilder
                ->andWhere('l.slug IN (:slugs)')
                ->setParameter('slugs', $criteria->getLineItemSlugs());
        }

        if ($criteria->hasLineItemLabelsCriteria()) {
            $queryBuilder
                ->andWhere('l.label IN (:labels)')
                ->setParameter('labels', $criteria->getLineItemLabels());
        }

        if ($criteria->hasLineItemUrisCriteria()) {
            $queryBuilder
                ->andWhere('l.uri IN (:uris)')
                ->setParameter('uris', $criteria->getLineItemUris());
        }

        if ($criteria->hasLineItemStartAtCriteria()) {
            $queryBuilder
                ->andWhere('l.startAt >= (:startAt)')
                ->setParameter('startAt', $criteria->getLineItemStartAt());
        }

        if ($criteria->hasLineItemEndAtCriteria()) {
            $queryBuilder
                ->andWhere('l.endAt <= (:endAt)')
                ->setParameter('endAt', $criteria->getLineItemEndAt());
        }
    }

    public function createOrUpdate(LineItem $lineItem): LineItem
    {
        try {
            $this->persist($lineItem);
            $this->flush();

            return $lineItem;
        } catch (UniqueConstraintViolationException $exception) {
            $this->registry->resetManager();

            return $this->findOneBy(['slug' => $lineItem->getSlug()]);
        }
    }
}
