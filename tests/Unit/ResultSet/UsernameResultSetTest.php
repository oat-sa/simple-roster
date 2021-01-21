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

namespace OAT\SimpleRoster\Tests\Unit\ResultSet;

use OAT\SimpleRoster\Model\UsernameCollection;
use OAT\SimpleRoster\ResultSet\UsernameResultSet;
use PHPUnit\Framework\TestCase;

class UsernameResultSetTest extends TestCase
{
    public function testItCanIterateThroughCollection(): void
    {
        $expectedCollection = (new UsernameCollection())
            ->add('username1')
            ->add('username2');

        $subject = new UsernameResultSet($expectedCollection, true, null);

        self::assertCount(2, $subject);
        self::assertSame('username1', $subject->getIterator()->offsetGet(0));
        self::assertSame('username2', $subject->getIterator()->offsetGet(1));
    }
}
