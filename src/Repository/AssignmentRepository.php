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

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use OAT\SimpleRoster\Entity\Assignment;

class AssignmentRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function findById(int $assignmentId): Assignment
    {
        $assignment = $this
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.id = :id')
            ->setParameter('id', $assignmentId)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $assignment) {
            throw new EntityNotFoundException(
                sprintf("Assignment with id = '%d' cannot be found.", $assignmentId)
            );
        }

        return $assignment;
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function findByLineItemId(int $lineItemId): ?Assignment
    {
        $assignment = $this
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.lineItemId = :line_item_id')
            ->setParameter('line_item_id', $lineItemId)
            ->orderBy('a.user', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $assignment;
    }

    /**
     * @return Paginator|Assignment[]
     */
    public function findByStateAndUpdatedAtPaged(
        string $state,
        DateTime $updatedAt,
        int $offset = null,
        int $limit = null
    ): Paginator {
        $queryBuilder = $this
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.state = :state')
            ->andWhere('a.updatedAt <= :updatedAt')
            ->setParameter('state', $state)
            ->setParameter('updatedAt', $updatedAt);

        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return new Paginator($queryBuilder->getQuery(), false);
    }
}
