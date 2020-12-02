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

namespace OAT\SimpleRoster\Tests\Unit\Message;

use OAT\SimpleRoster\Message\WarmUpGroupedUserCacheMessage;
use PHPUnit\Framework\TestCase;
use stdClass;

class WarmUpGroupedUserCacheMessageTest extends TestCase
{
    public function testIfUsernamesCanBeRetrieved(): void
    {
        $expectedUsernames = ['username1', 'username2', 'username3'];

        self::assertSame($expectedUsernames, (new WarmUpGroupedUserCacheMessage($expectedUsernames))->getUsernames());
    }

    public function testIfNonStringValuesAreFilteredOut(): void
    {
        $expectedUsernames = ['username1', 'username2', 'username3'];
        $testUsernames = ['username1', 23, false, 'username2', $this, new stdClass(), 'username3'];

        self::assertSame($expectedUsernames, (new WarmUpGroupedUserCacheMessage($testUsernames))->getUsernames());
    }
}
