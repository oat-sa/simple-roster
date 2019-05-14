<?php declare(strict_types=1);
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

namespace App\Repository;

use App\Entity\User;
use App\Generator\UserCacheIdGenerator;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends AbstractRepository
{
    /** @var UserCacheIdGenerator */
    private $userCacheIdGenerator;

    /** @var int */
    private $userCacheTtl;

    public function __construct(RegistryInterface $registry, UserCacheIdGenerator $userCacheIdGenerator, int $userCacheTtl)
    {
        parent::__construct($registry, User::class);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->userCacheTtl = $userCacheTtl;
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function getByUsernameWithAssignments(string $username): User
    {
        $user = $this
            ->createQueryBuilder('u')
            ->select('u, a, l, i')
            ->leftJoin('u.assignments', 'a')
            ->leftJoin('a.lineItem', 'l')
            ->leftJoin('l.infrastructure', 'i')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->useResultCache(true, $this->userCacheTtl, $this->userCacheIdGenerator->generate($username))
            ->getOneOrNullResult();

        if (null === $user) {
            throw new EntityNotFoundException(sprintf("User with username = '%s' cannot be found.", $username));
        }

        return $user;
    }
}
