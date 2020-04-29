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

class AssignmentDto
{
    /** @var int */
    private $id;

    /** @var string */
    private $state;

    /** @var int */
    private $userId;

    /** @var int */
    private $lineItemId;

    public function __construct(int $id, string $state, int $userId, int $lineItemId)
    {
        $this->id = $id;
        $this->state = $state;
        $this->userId = $userId;
        $this->lineItemId = $lineItemId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getLineItemId(): int
    {
        return $this->lineItemId;
    }
}
