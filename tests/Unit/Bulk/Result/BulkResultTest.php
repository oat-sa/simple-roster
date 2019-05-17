<?php declare(strict_types=1);
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

namespace App\Tests\Unit\Bulk\Result;

use App\Bulk\Operation\BulkOperation;
use App\Bulk\Result\BulkResult;
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

        $this->assertEquals(
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

        $this->assertEquals(
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
}
