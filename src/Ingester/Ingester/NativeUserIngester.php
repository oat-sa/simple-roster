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

namespace App\Ingester\Ingester;

use App\DataTransferObject\AssignmentDtoCollection;
use App\DataTransferObject\UserDtoCollection;
use App\Repository\NativeAssignmentRepository;
use App\Repository\NativeUserRepository;

class NativeUserIngester
{
    /** @var NativeUserRepository */
    private $userRepository;

    /** @var NativeAssignmentRepository */
    private $assignmentRepository;

    public function __construct(NativeUserRepository $userRepository, NativeAssignmentRepository $assignmentRepository)
    {
        $this->userRepository = $userRepository;
        $this->assignmentRepository = $assignmentRepository;
    }

    public function ingest(UserDtoCollection $users): void
    {
        $existingUsernames = [];
        // TODO replace with native query after develop merge
        foreach ($this->userRepository->findBy(['username' => $users->getAllUsernames()]) as $existingUser) {
            $existingUsernames[$existingUser->getUsername()] = $existingUser->getId();
        }

        $assignmentsToIngest = new AssignmentDtoCollection();
        foreach ($users as $user) {
            $assignmentsToIngest->merge($user->getAssignments());
            if (array_key_exists($user->getUsername(), $existingUsernames)) {
                $users->remove($user);

                $user->assignUserIdForAssignments($existingUsernames[$user->getUsername()]);
            }
        }

        $this->userRepository->insertMultiple($users);

        $this->assignmentRepository->insertMultiple($assignmentsToIngest);
    }
}
