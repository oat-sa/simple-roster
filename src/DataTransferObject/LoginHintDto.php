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
    /** @var string */
    private $username;

    /** @var string */
    private $groupId;

    /** @var string */
    private $slug;

    public function __construct(string $username, string $groupId, string $slug)
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username cannot be empty');
        }

        if (empty($slug)) {
            throw new InvalidArgumentException('Slug cannot be empty');
        }

        $this->username = $username;
        $this->groupId = $groupId;
        $this->slug = $slug;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function __toString(): string
    {
        return implode('::', get_object_vars($this));
    }
}
