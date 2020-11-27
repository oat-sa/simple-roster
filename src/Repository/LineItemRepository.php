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

use Doctrine\Persistence\ManagerRegistry;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Generator\LineItemCacheIdGenerator;
use OAT\SimpleRoster\Model\LineItemCollection;

/**
 * @method LineItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method LineItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method LineItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LineItemRepository extends AbstractRepository
{
    /** @var int */
    private $lineItemCacheTtl;

    /** @var LineItemCacheIdGenerator */
    private $cacheIdGenerator;

    public function __construct(
        ManagerRegistry $registry,
        LineItemCacheIdGenerator $cacheIdGenerator,
        int $lineItemCacheTtl = 3600
    ) {
        parent::__construct($registry, LineItem::class);
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

    public function findById(int $id): ?LineItem
    {
        return  $this
            ->createQueryBuilder('l')
            ->select('l')
            ->where('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->enableResultCache($this->lineItemCacheTtl, $this->cacheIdGenerator->generate($id))
            ->getOneOrNullResult();
    }
}
