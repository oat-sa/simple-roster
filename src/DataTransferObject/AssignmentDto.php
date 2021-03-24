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

use Symfony\Component\Uid\UuidV6;

class AssignmentDto
{
    /** @var string */
    private $state;

    /** @var UuidV6 */
    private $lineItemId;

    /** @var string */
    private $username;

    /** @var int|null */
    private $userId;

    public function __construct(string $state, UuidV6 $lineItemId, string $username, int $userId = null)
    {
        $this->state = $state;
        $this->lineItemId = $lineItemId;
        $this->username = $username;
        $this->userId = $userId;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getLineItemId(): UuidV6
    {
        return $this->lineItemId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
