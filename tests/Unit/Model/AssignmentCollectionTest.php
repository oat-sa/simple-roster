<?php

/*
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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

namespace OAT\SimpleRoster\Tests\Unit\Model;

use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Model\AssignmentCollection;
use PHPUnit\Framework\TestCase;

class AssignmentCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new AssignmentCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new AssignmentCollection());
    }

    public function testIfLineItemCanBeAdded(): void
    {
        $assignment = (new Assignment())->setState(Assignment::STATE_CANCELLED);
        $collection = (new AssignmentCollection())->add($assignment);

        self::assertCount(1, $collection);
        self::assertSame($assignment, $collection->getIterator()->current());
    }

    public function testJsonSerialization(): void
    {
        $assignment1 = (new Assignment())->setState(Assignment::STATE_CANCELLED);
        $assignment2 = (new Assignment())->setState(Assignment::STATE_COMPLETED);

        $collection = new AssignmentCollection([$assignment1, $assignment2]);

        self::assertSame([$assignment1, $assignment2], $collection->jsonSerialize());
    }

    public function testIsEmpty(): void
    {
        $assignment1 = (new Assignment())->setState(Assignment::STATE_CANCELLED);
        $assignment2 = (new Assignment())->setState(Assignment::STATE_COMPLETED);

        $collection1 = new AssignmentCollection([$assignment1, $assignment2]);

        self::assertFalse($collection1->isEmpty());

        $collection2 = new AssignmentCollection([]);

        self::assertTrue($collection2->isEmpty());
    }
}
