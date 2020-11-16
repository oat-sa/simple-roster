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
        $this->expectExceptionMessage('Username cannot be empty');

        new LoginHintDto('', 'group', 'slug');
    }

    public function testItThrowsExceptionIfEmptySlugReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slug cannot be empty');

        new LoginHintDto('username', 'group', '');
    }

    public function testLoginHintDtoCreation(): void
    {
        $loginHintDto = new LoginHintDto('username', 'groupId', 'slug');

        $this->assertSame('username', $loginHintDto->getUsername());
        $this->assertSame('groupId', $loginHintDto->getGroupId());
        $this->assertSame('slug', $loginHintDto->getSlug());
    }
}
