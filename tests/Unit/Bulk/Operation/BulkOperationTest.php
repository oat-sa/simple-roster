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

namespace App\Tests\Unit\Bulk\Operation;

use App\Bulk\Operation\BulkOperation;
use PHPUnit\Framework\TestCase;

class BulkOperationTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new BulkOperation('identifier', BulkOperation::TYPE_UPDATE, ['key' => 'value']);

        $this->assertEquals('identifier', $subject->getIdentifier());
        $this->assertEquals(BulkOperation::TYPE_UPDATE, $subject->getType());
        $this->assertEquals(['key' => 'value'], $subject->getAttributes());
        $this->assertEquals('value', $subject->getAttribute('key'));
    }
}
