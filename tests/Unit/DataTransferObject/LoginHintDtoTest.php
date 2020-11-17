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

namespace OAT\SimpleRoster\Tests\Unit\DataTransferObject;

use InvalidArgumentException;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use PHPUnit\Framework\TestCase;

class LoginHintDtoTest extends TestCase
{
    public function testItThrowsExceptionIfEmptyUsernameReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username can\'t be empty.');

        new LoginHintDto('', 1);
    }

    public function testItThrowsExceptionIfEmptySlugReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Assignment ID can\'t be 0.');

        new LoginHintDto('username', 0);
    }

    public function testLoginHintDtoCreation(): void
    {
        $loginHintDto = new LoginHintDto('username', 1);

        self::assertSame('username', $loginHintDto->getUsername());
        self::assertSame(1, $loginHintDto->getAssignmentId());
    }
}
