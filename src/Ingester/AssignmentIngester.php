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

namespace OAT\SimpleRoster\Ingester;

use Doctrine\ORM\ORMException;
use Doctrine\Persistence\Mapping\MappingException;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Exception\UserNotFoundException;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\UserRepository;
use Symfony\Component\Uid\UuidV6;
use Throwable;

class AssignmentIngester
{
    /** @var UserRepository */
    private $userRepository;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    public function __construct(UserRepository $userRepository, AssignmentRepository $assignmentRepository)
    {
        $this->userRepository = $userRepository;
        $this->assignmentRepository = $assignmentRepository;
    }

    /**
     * @throws ORMException
     * @throws UserNotFoundException
     * @throws MappingException
     * @throws Throwable
     */
    public function ingest(AssignmentDtoCollection $assignments): void
    {
        $existingUsernames = [];
        foreach ($this->userRepository->findUsernames($assignments->getAllUsernames()) as $existingUser) {
            $existingUsernames[$existingUser['username']] = $existingUser['id'];
        }

        foreach ($assignments as $assignment) {
            if (!array_key_exists($assignment->getUsername(), $existingUsernames)) {
                throw new UserNotFoundException(
                    sprintf("User with username '%s' cannot not found.", $assignment->getUsername())
                );
            }

            $assignment->setUserId(new UuidV6($existingUsernames[$assignment->getUsername()]));
        }

        $this->assignmentRepository->insertMultipleNatively($assignments);
    }
}
