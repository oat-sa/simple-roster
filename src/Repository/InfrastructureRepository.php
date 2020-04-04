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

namespace App\Repository;

use App\Entity\Infrastructure;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Infrastructure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Infrastructure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Infrastructure[]    findAll()
 * @method Infrastructure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InfrastructureRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Infrastructure::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getByLtiKey(string $ltiKey): ?Infrastructure
    {
        return $this
            ->createQueryBuilder('i')
            ->where('i.ltiKey = :ltiKey')
            ->setParameter('ltiKey', $ltiKey)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
