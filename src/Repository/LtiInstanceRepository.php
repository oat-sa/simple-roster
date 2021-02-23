<?php

/*
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

namespace OAT\SimpleRoster\Repository;

use Doctrine\Persistence\ManagerRegistry;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;

/**
 * @method LtiInstance|null   find($id, $lockMode = null, $lockVersion = null)
 * @method LtiInstance|null   findOneBy(array $criteria, array $orderBy = null)
 * @method LtiInstance[]|null findAll()
 * @method LtiInstance[]|null findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LtiInstanceRepository extends AbstractRepository
{
    public const CACHE_ID_ALL_LTI_INSTANCES = 'lti_instances.all';

    /** @var int */
    private $ltiInstancesCacheTtl;

    public function __construct(ManagerRegistry $registry, int $ltiInstancesCacheTtl)
    {
        parent::__construct($registry, LtiInstance::class);

        $this->ltiInstancesCacheTtl = $ltiInstancesCacheTtl;
    }

    public function findAllAsCollection(): UniqueLtiInstanceCollection
    {
        $ltiInstances = $this->createQueryBuilder('l')
            ->select('l')
            ->getQuery()
            ->enableResultCache($this->ltiInstancesCacheTtl, self::CACHE_ID_ALL_LTI_INSTANCES)
            ->getResult();

        return new UniqueLtiInstanceCollection(...$ltiInstances);
    }
}
