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
    /** @var int */
    private $id;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var AssignmentDto */
    private $assignment;

    /** @var string[] */
    private $roles;

    /** @var string|null */
    private $groupId;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        int $id,
        string $username,
        string $password,
        AssignmentDto $assignment,
        array $roles,
        ?string $groupId
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->assignment = $assignment;
        $this->roles = $roles;
        $this->groupId = $groupId;

        if ($id !== $assignment->getUserId()) {
            throw new InvalidArgumentException("User id must match with assignment's user id.");
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function getAssignment(): AssignmentDto
    {
        return $this->assignment;
    }
}
