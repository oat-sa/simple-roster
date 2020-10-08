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

namespace App\Repository;

use App\DataTransferObject\AssignmentDtoCollection;
use App\Entity\Assignment;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\Mapping\MappingException;

class NativeAssignmentRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignment::class);
    }

    /**
     * @throws ORMException
     * @throws MappingException
     */
    public function insertMultiple(AssignmentDtoCollection $assignments): void
    {
        $queryParts = [];
        foreach ($assignments as $assignmentDto) {
            $queryParts[] = sprintf(
                "(%s, %s, %s, '%s', %d)",
                $assignmentDto->getId(),
                $assignmentDto->getUserId(),
                $assignmentDto->getLineItemId(),
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

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getAvailableAssignmentStartIndex(): int
    {
        $index = $this
            ->createQueryBuilder('a')
            ->select('MAX(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$index + 1;
    }

    /**
     * @codeCoverageIgnore Cannot be tested with SQLite database
     *
     * @throws ORMException
     */
    public function refreshSequence(): void
    {
        $this
            ->getEntityManager()
            ->createNativeQuery(
                "SELECT SETVAL('assignments_id_seq', COALESCE(MAX(id), 1)) FROM assignments",
                new ResultSetMapping()
            )
            ->execute();
    }
}
