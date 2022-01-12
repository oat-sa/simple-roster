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

namespace OAT\SimpleRoster\DataTransferObject;

class UserCreationResult
{
    private string $message;
    private array $nonExistingLineItems;

    public function __construct(string $message, array $nonExistingLineItems)
    {
        $this->message = $message;
        $this->nonExistingLineItems = $nonExistingLineItems;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string[]
     */
    public function getNonExistingLineItems(): array
    {
        return $this->nonExistingLineItems;
    }
}
