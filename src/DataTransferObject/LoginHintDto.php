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

namespace OAT\SimpleRoster\DataTransferObject;

use InvalidArgumentException;

class LoginHintDto
{
    public const LOGIN_HINT_SEPARATOR = '::';

    /** @var string */
    private $username;

    /** @var int */
    private $assignmentId;

    public function __construct(string $username, int $assignmentId)
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username can\'t be empty.');
        }

        if ($assignmentId === 0) {
            throw new InvalidArgumentException('Assignment ID can\'t be 0.');
        }

        $this->username = $username;
        $this->assignmentId = $assignmentId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAssignmentId(): int
    {
        return $this->assignmentId;
    }

    public function __toString(): string
    {
        return implode(self::LOGIN_HINT_SEPARATOR, get_object_vars($this));
    }
}
