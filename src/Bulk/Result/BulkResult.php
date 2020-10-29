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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace App\Bulk\Result;

use App\Bulk\Operation\BulkOperation;
use JsonSerializable;

class BulkResult implements JsonSerializable
{
    /** @var bool[] */
    private $results = [];

    /** @var int */
    private $failuresCount = 0;

    public function addBulkOperationSuccess(BulkOperation $operation): self
    {
        return $this->addBulkOperationResult($operation, true);
    }

    public function addBulkOperationFailure(BulkOperation $operation): self
    {
        $this->failuresCount++;

        return $this->addBulkOperationResult($operation, false);
    }

    public function hasFailures(): bool
    {
        return $this->failuresCount > 0;
    }

    public function jsonSerialize(): array
    {
        return [
            'data' => [
                'applied' => !$this->hasFailures(),
                'results' => $this->results,
            ],
        ];
    }

    private function addBulkOperationResult(BulkOperation $operation, bool $result): self
    {
        $this->results[$operation->getIdentifier()] = $result;

        return $this;
    }
}
