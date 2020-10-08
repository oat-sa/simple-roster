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

use App\Exception\UserNotFoundException;
use ArrayIterator;
use Countable;
use IteratorAggregate;

class UserDtoCollection implements Countable, IteratorAggregate
{
    /** @var UserDto[] */
    private $users = [];

    public function add(UserDto $user): self
    {
        if (!$this->containsWithUsername($user->getUsername())) {
            $this->users[$user->getUsername()] = $user;
        }

        return $this;
    }

    public function remove(UserDto $user): self
    {
        if ($this->containsWithUsername($user->getUsername())) {
            unset($this->users[$user->getUsername()]);
        }

        return $this;
    }

    public function clear(): self
    {
        $this->users = [];

        return $this;
    }

    /**
     * @return ArrayIterator|UserDto[]
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->users);
    }

    public function count()
    {
        return count($this->users);
    }

    public function isEmpty(): bool
    {
        return count($this) === 0;
    }

    public function getByUsername(string $username): UserDto
    {
        if (!$this->containsWithUsername($username)) {
            throw new UserNotFoundException(
                sprintf("User with username '%s' is not found.", $username)
            );
        }

        return $this->users[$username];
    }

    public function containsWithUsername(string $username): bool
    {
        return isset($this->users[$username]);
    }

    /**
     * @return string[]
     */
    public function getAllUsernames(): array
    {
        return array_map(static function (UserDto $user) {
            return $user->getUsername();
        }, $this->users);
    }
}
