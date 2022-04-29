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
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Model\UserCollection;
use PHPUnit\Framework\TestCase;

class UserCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new UserCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new UserCollection());
    }

    public function testIfLineItemCanBeAdded(): void
    {
        $user = (new User())->setUsername('test_one');
        $collection = (new UserCollection())->add($user);

        self::assertCount(1, $collection);
        self::assertSame($user, $collection->getIterator()->current());
    }

    public function testJsonSerialization(): void
    {
        $user1 = (new User())->setUsername('test_one');
        $user2 = (new User())->setUsername('test_two');

        $collection = new UserCollection([$user1, $user2]);

        self::assertSame([$user1, $user2], $collection->jsonSerialize());
    }

    public function testIsEmpty(): void
    {
        $user1 = (new User())->setUsername('test_one');
        $user2 = (new User())->setUsername('test_two');

        $collection1 = new UserCollection([$user1, $user2]);

        self::assertFalse($collection1->isEmpty());

        $collection2 = new UserCollection([]);

        self::assertTrue($collection2->isEmpty());
    }
}
