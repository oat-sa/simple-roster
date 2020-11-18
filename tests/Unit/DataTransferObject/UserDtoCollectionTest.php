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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\DataTransferObject;

use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use PHPUnit\Framework\TestCase;

class UserDtoCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new UserDtoCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new UserDtoCollection());
    }

    public function testIfUsersCanBeAdded(): void
    {
        $subject = new UserDtoCollection();
        self::assertCount(0, $subject);
        self::assertTrue($subject->isEmpty());

        $user1 = new UserDto('user1', 'password1');
        $subject->add($user1);

        self::assertCount(1, $subject);
        self::assertFalse($subject->isEmpty());

        $user2 = new UserDto('user2', 'password2');
        $subject->add($user2);

        self::assertCount(2, $subject);
        self::assertFalse($subject->isEmpty());
    }
}
