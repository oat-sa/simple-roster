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

use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\Entity\User;
use Throwable;

class NativeUserRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws MappingException
     */
    public function insertMultiple(UserDtoCollection $users): void
    {
        $queryParts = [];
        foreach ($users as $user) {
            $queryParts[] = sprintf(
                "('%s', '%s', '%s', '[]', '%s')",
                (string)$user->getId(),
                $user->getUsername(),
                $user->getPassword(),
                $user->getGroupId()
            );
        }

        $query = sprintf(
            'INSERT INTO users (id, username, password, roles, group_id) VALUES %s',
            implode(',', $queryParts)
        );

        $this->_em->createNativeQuery($query, new ResultSetMapping())->execute();
        $this->_em->clear();
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
            implode(',', array_map(static function (string $username): string {
                return "'" . $username . "'";
            }, $usernames))
        );

        $statement = $this->_em->getConnection()->prepare($query);
        $statement->execute();

        return $statement->fetchAllAssociative();
    }
}
