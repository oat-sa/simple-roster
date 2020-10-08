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

namespace App\DataTransferObject;

use InvalidArgumentException;

class UserDto
{
    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string|null */
    private $groupId;

    /** @var AssignmentDtoCollection */
    private $assignments;

    public function __construct(
        string $username,
        string $password,
        string $groupId = null,
        AssignmentDtoCollection $assignments = null
    ) {
        if (empty($username)) {
            throw new InvalidArgumentException('Username cannot be empty');
        }

        if (empty($password)) {
            throw new InvalidArgumentException('Password cannot be empty');
        }

        if (null !== $groupId && empty($groupId)) {
            throw new InvalidArgumentException('Group id cannot be empty');
        }

        $this->username = $username;
        $this->password = $password;
        $this->groupId = $groupId;
        $this->assignments = $assignments ?? new AssignmentDtoCollection();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function getAssignments(): AssignmentDtoCollection
    {
        return $this->assignments;
    }

    public function addAssignment(AssignmentDto $assignmentDto): self
    {
        $this->assignments->add($assignmentDto);

        return $this;
    }
}
