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

use App\Entity\User;
use App\Exception\InvalidUsernameException;
use App\Generator\UserCacheIdGenerator;
use App\Model\UsernameCollection;
use App\ResultSet\UsernameResultSet;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;

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

    public function __construct(
        ManagerRegistry $registry,
        UserCacheIdGenerator $userCacheIdGenerator,
        int $userCacheTtl
    ) {
        parent::__construct($registry, User::class);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->userCacheTtl = $userCacheTtl;
    }

    /**
     * @throws InvalidUsernameException
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function getByUsernameWithAssignments(string $username): User
    {
        if (empty($username)) {
            throw new InvalidUsernameException('Empty username received.');
        }

        $user = $this
            ->createQueryBuilder('u')
            ->select('u, a, l, i')
            ->innerJoin('u.assignments', 'a')
            ->innerJoin('a.lineItem', 'l')
            ->innerJoin('l.infrastructure', 'i')
            ->where('u.username = :username')
            ->setParameter('username', $username)
            ->getQuery()
            ->enableResultCache($this->userCacheTtl, $this->userCacheIdGenerator->generate($username))
            ->getOneOrNullResult();

        if (null === $user) {
            throw new EntityNotFoundException(sprintf("User with username = '%s' cannot be found.", $username));
        }

        return $user;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findAllUsernamePaged(int $limit, int $lastUserId = null): UsernameResultSet
    {
        if ($limit < 1) {
            throw new InvalidArgumentException("Invalid 'limit' parameter received.");
        }

        $queryBuilder = $this->createQueryBuilder('u')
            ->distinct()
            ->select('u.id', 'u.username')
            ->orderBy('u.id')
            ->setMaxResults($limit + 1);

        if (null !== $lastUserId) {
            $queryBuilder
                ->where('u.id > :lastUserId')
                ->setParameter('lastUserId', $lastUserId);
        }

        $userIds = [];
        $usernameCollection = new UsernameCollection();
        $result = $queryBuilder->getQuery()->getResult();
        foreach ($result as $row) {
            if (count($usernameCollection) < $limit) {
                $usernameCollection->add($row['username']);
                $userIds[] = $row['id'];
            }
        }

        return new UsernameResultSet(
            $usernameCollection,
            count($result) === $limit + 1,
            $userIds[$limit - 1] ?? null
        );
    }

    public function findAllUsernameByUserIdsPaged(int ...$userIds): Paginator
    {
        // TODO
    }

    public function findAllUsernameByLineItemIdsPaged(int ...$lineItemIds): Paginator
    {
        // TODO
    }
}
