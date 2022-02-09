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

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use InvalidArgumentException;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\InvalidUsernameException;
use OAT\SimpleRoster\Generator\UserCacheIdGenerator;
use OAT\SimpleRoster\Model\UserCollection;
use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\Repository\Criteria\FindUserCriteria;
use OAT\SimpleRoster\ResultSet\UsernameResultSet;

class UserRepository extends AbstractRepository
{
    private UserCacheIdGenerator $userCacheIdGenerator;
    private int $userCacheTtl;

    public function __construct(
        ManagerRegistry      $registry,
        UserCacheIdGenerator $userCacheIdGenerator,
        int                  $userCacheTtl
    )
    {
        parent::__construct($registry, User::class);

        $this->userCacheIdGenerator = $userCacheIdGenerator;
        $this->userCacheTtl = $userCacheTtl;
    }

    /**
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function findByUsernameWithAssignments(string $username): User
    {
        if (empty($username)) {
            throw new InvalidUsernameException('Empty username received.');
        }

        /** @var User|null $user */
        $user = $this
            ->createQueryBuilder('u')
            ->select('u, a')
            ->innerJoin('u.assignments', 'a')
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

    public function findAllByCriteria(FindUserCriteria $criteria): UserCollection
    {
        $queryBuilder = $this
            ->createQueryBuilder('u')
            ->select('u');

        $this->applyFindCriteria($queryBuilder, $criteria);

        return new UserCollection($queryBuilder->getQuery()->getResult());
    }

    /**
     * @throws Exception
     */
    public function findAllUsernamesPaged(
        int              $limit,
        ?int             $lastUserId,
        FindUserCriteria $criteria = null
    ): UsernameResultSet
    {
        if ($limit < 1) {
            throw new InvalidArgumentException("Invalid 'limit' parameter received.");
        }

        if (null === $criteria) {
            $criteria = new FindUserCriteria();
        }

        $queryBuilder = $this->createQueryBuilder('u')
            ->select('u.id', 'u.username')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($limit + 1);

        if (null !== $lastUserId) {
            $queryBuilder
                ->andWhere('u.id > :lastUserId')
                ->setParameter('lastUserId', $lastUserId);
        }

        $this->applyFindCriteria($queryBuilder, $criteria);

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

    public function countByCriteria(FindUserCriteria $criteria = null): int
    {
        if (!$criteria) {
            $criteria = new FindUserCriteria();
        }

        $queryBuilder = $this->createQueryBuilder('u')
            ->select('COUNT(u.id) AS number_of_users');

        if ($criteria->hasUsernameCriterion()) {
            $queryBuilder
                ->andWhere('u.username IN (:usernames)')
                ->setParameter('usernames', $criteria->getUsernameCriterion());
        }

        if ($criteria->hasLineItemSlugCriterion()) {
            $queryBuilder
                ->leftJoin('u.assignments', 'a')
                ->leftJoin('a.lineItem', 'l')
                ->andWhere('l.slug IN (:lineItemSlugs)')
                ->setParameter('lineItemSlugs', $criteria->getLineItemSlugCriterion());
        }

        if ($criteria->hasEuclideanDivisionCriterion()) {
            $queryBuilder
                ->where('MOD(u.id, :modulo) = :remainder')
                ->setParameter('modulo', $criteria->getEuclideanDivisionCriterion()->getModulo())
                ->setParameter('remainder', $criteria->getEuclideanDivisionCriterion()->getRemainder());
        }

        $result = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();

        return null === $result ? 0 : (int)$result['number_of_users'];
    }

    protected function applyFindCriteria(QueryBuilder $queryBuilder, FindUserCriteria $criteria): void
    {
        if ($criteria->hasUsernameCriterion()) {
            $queryBuilder
                ->andWhere('u.username IN (:usernames)')
                ->setParameter('usernames', $criteria->getUsernameCriterion());
        }

        if ($criteria->hasLineItemSlugCriterion()) {
            $queryBuilder
                ->innerJoin('u.assignments', 'a')
                ->innerJoin('a.lineItem', 'l')
                ->andWhere('l.slug IN (:lineItemSlugs)')
                ->setParameter('lineItemSlugs', $criteria->getLineItemSlugCriterion());
        }

        if ($criteria->hasEuclideanDivisionCriterion()) {
            $queryBuilder
                ->andWhere('MOD(u.id, :modulo) = :remainder')
                ->setParameter('modulo', $criteria->getEuclideanDivisionCriterion()->getModulo())
                ->setParameter('remainder', $criteria->getEuclideanDivisionCriterion()->getRemainder());
        }
    }
}
