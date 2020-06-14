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

namespace App\Tests\Unit\DataTransferObject;

use App\DataTransferObject\AssignmentDto;
use App\DataTransferObject\UserDto;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserDtoTest extends TestCase
{
    public function testItThrowsExceptionIfUserIdsAreNotMatchingWithAssignment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("User id must match with assignment's user id.");

        $assignment = new AssignmentDto(1, 'test', 2, 1);
        new UserDto(1, 'test', 'test', $assignment, null);
    }
}
