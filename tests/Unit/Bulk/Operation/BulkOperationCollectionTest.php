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

namespace OAT\SimpleRoster\Tests\Unit\Bulk\Operation;

use ArrayIterator;
use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use PHPUnit\Framework\TestCase;

class BulkOperationCollectionTest extends TestCase
{
    public function testItCanAddAnRetrieveBulkOperations(): void
    {
        $subject = new BulkOperationCollection();

        $operation1 = new BulkOperation('identifier1', BulkOperation::TYPE_UPDATE);
        $operation2 = new BulkOperation('identifier2', BulkOperation::TYPE_CREATE);

        $subject
            ->add($operation1)
            ->add($operation2);

        /** @var ArrayIterator $iterator */
        $iterator = $subject->getIterator();

        self::assertCount(2, $subject);
        self::assertSame(
            [
                'identifier1' => $operation1,
                'identifier2' => $operation2,
            ],
            $iterator->getArrayCopy()
        );
    }
}
