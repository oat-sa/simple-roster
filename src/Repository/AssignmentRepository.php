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
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\Assignment;
use Symfony\Component\Uid\UuidV6;

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
    public function findById(UuidV6 $assignmentId): Assignment
    {
        $assignment = $this
            ->createQueryBuilder('a')
            ->select('a')
            ->where('a.id = :id')
            ->setParameter('id', $assignmentId, 'uuid')
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $assignment) {
            throw new EntityNotFoundException(sprintf("Assignment with id = '%s' cannot be found.", $assignmentId));
        }

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

    /**
     * @throws ORMException
     * @throws MappingException
     */
    public function insertMultipleNatively(AssignmentDtoCollection $assignments): void
    {
        $queryParts = [];
        foreach ($assignments as $assignmentDto) {
            $queryParts[] = sprintf(
                "('%s', '%s', '%s', '%s', %s)",
                (string)$assignmentDto->getId(),
                (string)$assignmentDto->getUserId(),
                (string)$assignmentDto->getLineItemId(),
                $assignmentDto->getState(),
                0
            );
        }

        $query = sprintf(
            'INSERT INTO assignments (id, user_id, line_item_id, state, attempts_count) VALUES %s',
            implode(',', $queryParts)
        );

        $this->_em->createNativeQuery($query, new ResultSetMapping())->execute();
        $this->_em->clear();
    }
}
