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

namespace OAT\SimpleRoster\Tests\Unit\Bulk\Result;

use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Result\BulkResult;
use PHPUnit\Framework\TestCase;

class BulkResultTest extends TestCase
{
    public function testJsonSerializationWithBulkOperationSuccesses(): void
    {
        $subject = new BulkResult();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE);

        $subject
            ->addBulkOperationSuccess($operation1)
            ->addBulkOperationSuccess($operation2);

        self::assertSame(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        'identifier1' => true,
                        'identifier2' => true,
                    ]
                ]
            ],
            $subject->jsonSerialize()
        );
    }

    public function testJsonSerializationWithBulkOperationFailures(): void
    {
        $subject = new BulkResult();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE);

        $subject
            ->addBulkOperationSuccess($operation1)
            ->addBulkOperationFailure($operation2);

        self::assertSame(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        'identifier1' => true,
                        'identifier2' => false,
                    ]
                ]
            ],
            $subject->jsonSerialize()
        );
    }

    public function testItReturnsSuccessfulBulkOperationIdentifiers(): void
    {
        $subject = new BulkResult();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE);
        $operation3 = new BulkOperation('identifier3', BulkOperation::TYPE_CREATE);
        $operation4 = new BulkOperation('identifier4', BulkOperation::TYPE_UPDATE);

        $subject
            ->addBulkOperationSuccess($operation1)
            ->addBulkOperationSuccess($operation3)
            ->addBulkOperationFailure($operation2)
            ->addBulkOperationFailure($operation4);

        self::assertSame(
            ['identifier1', 'identifier3'],
            $subject->getSuccessfulBulkOperationIdentifiers()
        );
    }
}
