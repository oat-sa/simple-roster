<?php

declare(strict_types=1);

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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Ingester\Result;

class IngesterResultFailure
{
    /** @var int */
    private $lineNumber;

    /** @var array */
    private $data;

    /** @var string */
    private $reason;

    public function __construct(int $lineNumber, array $data, string $reason)
    {
        $this->lineNumber = $lineNumber;
        $this->data = $data;
        $this->reason = $reason;
    }

    public function getLineNumber(): int
    {
        return $this->lineNumber;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
