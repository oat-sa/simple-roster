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

namespace App\Tests\Unit\Model;

use App\Entity\LineItem;
use App\Exception\LineItemNotFoundException;
use App\Model\LineItemCollection;
use Countable;
use IteratorAggregate;
use PHPUnit\Framework\TestCase;

class LineItemCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        $this->assertInstanceOf(Countable::class, new LineItemCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        $this->assertInstanceOf(IteratorAggregate::class, new LineItemCollection());
    }

    public function testIfLineItemCanBeAdded(): void
    {
        $lineItem = (new LineItem())->setSlug('test');
        $subject = (new LineItemCollection())->add($lineItem);

        $this->assertCount(1, $subject);
        $this->assertSame($lineItem, $subject->getIterator()->current());
    }

    public function testItThrowsExceptionIfLineItemCannotBeFoundBySlug(): void
    {
        $this->expectException(LineItemNotFoundException::class);
        $this->expectExceptionMessage("Line item with slug = 'unexpectedSlug' cannot be found in collection.");

        (new LineItemCollection())->getBySlug('unexpectedSlug');
    }
}
