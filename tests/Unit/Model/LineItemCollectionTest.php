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

namespace OAT\SimpleRoster\Tests\Unit\Model;

use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\LineItemNotFoundException;
use OAT\SimpleRoster\Model\LineItemCollection;
use PHPUnit\Framework\TestCase;

class LineItemCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new LineItemCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new LineItemCollection());
    }

    public function testIfLineItemCanBeAdded(): void
    {
        $lineItem = (new LineItem())->setSlug('test');
        $subject = (new LineItemCollection())->add($lineItem);

        self::assertCount(1, $subject);
        self::assertSame($lineItem, $subject->getIterator()->current());
    }

    public function testJsonSerialization(): void
    {
        $lineItem1 = (new LineItem())->setSlug('slug-1');
        $lineItem2 = (new LineItem())->setSlug('slug-2');

        $subject = (new LineItemCollection())->add($lineItem1)->add($lineItem2);

        self::assertSame([$lineItem1, $lineItem2], $subject->jsonSerialize());
    }

    public function testItThrowsExceptionIfLineItemCannotBeFoundBySlug(): void
    {
        $this->expectException(LineItemNotFoundException::class);
        $this->expectExceptionMessage("Line item with slug = 'unexpectedSlug' cannot be found in collection.");

        (new LineItemCollection())->getBySlug('unexpectedSlug');
    }
}
