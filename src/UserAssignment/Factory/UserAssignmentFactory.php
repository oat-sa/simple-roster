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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\UserAssignment\Factory;

use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Entity\Assignment;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Repository\NativeUserRepository;
use OAT\SimpleRoster\Ingester\AssignmentIngester;

class UserAssignmentFactory
{
    private UserPasswordHasherInterface $passwordHasher;
    private NativeUserRepository $userRepository;
    private AssignmentIngester $assignmentIngester;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        NativeUserRepository $userRepository,
        AssignmentIngester $assignmentIngester
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->assignmentIngester = $assignmentIngester;
    }

    public function createUserDtoCollection(
        UserDtoCollection $userDtoCollection,
        string $username,
        string $userPassword,
        string $userGroupId
    ): UserDtoCollection {
        return $userDtoCollection->add(
            new UserDto(
                $username,
                $this->passwordHasher->hashPassword(new User(), $userPassword),
                $userGroupId ? $userGroupId : null
            )
        );
    }

    public function createAssignmentDtoCollection(
        AssignmentDtoCollection $assignmentDtoCollection,
        int $lineKey,
        string $username
    ): AssignmentDtoCollection {

        return $assignmentDtoCollection->add(
            new AssignmentDto(
                Assignment::STATE_READY,
                $lineKey,
                $username
            )
        );
    }

    public function saveBulkUserAssignmentData(
        UserDtoCollection $userDtoCollection,
        AssignmentDtoCollection $assignmentDtoCollection
    ): void {
        if (!$userDtoCollection->isEmpty() && !$assignmentDtoCollection->isEmpty()) {
            $this->userRepository->insertMultiple($userDtoCollection);
            $this->assignmentIngester->ingest($assignmentDtoCollection);
        }
    }
}
