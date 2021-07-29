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

namespace OAT\SimpleRoster\Tests\Unit\ResultSet;

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\ResultSet\LineItemResultSet;
use PHPUnit\Framework\TestCase;

class LineItemResultSetTest extends TestCase
{
    public function testLineItemResultSet(): void
    {
        $lineItem1 = (new LineItem())->setSlug('slug1');
        $lineItem2 = (new LineItem())->setSlug('slug2');

        $expectedCollection = (new LineItemCollection())
            ->add($lineItem1)
            ->add($lineItem2);

        $subject = new LineItemResultSet($expectedCollection, true, null);

        self::assertCount(2, $subject);
        self::assertSame($lineItem1, $subject->getIterator()->offsetGet('slug1'));
        self::assertSame($lineItem2, $subject->getIterator()->offsetGet('slug2'));
        self::assertSame($expectedCollection, $subject->getLineItemCollection());
        self::assertSame([$lineItem1, $lineItem2], $subject->jsonSerialize());
        self::assertTrue($subject->hasMore());
        self::assertNull($subject->getLastLineItemId());
        self::assertFalse($subject->isEmpty());
    }
}
