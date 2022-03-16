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

namespace OAT\SimpleRoster\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\Entity\User;
use Throwable;

class NativeUserRepository extends AbstractRepository
{
    /** @var string */
    private string $kernelEnvironment;

    public function __construct(ManagerRegistry $registry, string $kernelEnvironment)
    {
        parent::__construct($registry, User::class);

        $this->kernelEnvironment = $kernelEnvironment;
    }

    /**
     * @throws ORMException
     * @throws MappingException
     */
    public function insertMultiple(UserDtoCollection $users): void
    {
        $queryParts = [];
        $userIndex = $this->findNextAvailableUserIndex();

        foreach ($users as $user) {
            $queryParts[] = sprintf(
                "(%s, '%s', '%s', '[]', '%s')",
                $userIndex,
                $user->getUsername(),
                $user->getPassword(),
                $user->getGroupId()
            );

            $userIndex++;
        }

        $query = sprintf(
            'INSERT INTO users (id, username, password, roles, group_id) VALUES %s',
            implode(',', $queryParts)
        );

        $this->_em->createNativeQuery($query, new ResultSetMapping())->execute();
        $this->_em->clear();
        $this->refreshSequence();
    }

    /**
     * @param string[] $usernames
     *
     * @throws Throwable
     */
    public function findUsernames(array $usernames): array
    {
        $query = sprintf(
            "SELECT id, username FROM users WHERE username IN (%s)",
            implode(',', array_map(static function (string $username) {
                return "'" . $username . "'";
            }, $usernames))
        );

        $statement = $this->_em->getConnection()->prepare($query);

        return $statement->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function findNextAvailableUserIndex(): int
    {
        $index = $this
            ->createQueryBuilder('u')
            ->select('MAX(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$index + 1;
    }

    /**
     * @codeCoverageIgnore Cannot be tested with SQLite database
     *
     * @throws ORMException
     */
    private function refreshSequence(): void
    {
        if ($this->kernelEnvironment !== 'test') {
            $this
                ->getEntityManager()
                ->createNativeQuery(
                    "SELECT SETVAL('users_id_seq', COALESCE(MAX(id), 1)) FROM users",
                    new ResultSetMapping()
                )
                ->execute();
        }
    }
}
