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

use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\Entity\Assignment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserDtoTest extends TestCase
{
    public function testItThrowsExceptionIfEmptyUsernameReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        new UserDto('', 'password');
    }

    public function testItThrowsExceptionIfEmptyPasswordReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password cannot be empty');

        new UserDto('username', '');
    }

    public function testItThrowsExceptionIfGroupIdIsSetBuItIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Group id cannot be empty');

        new UserDto('username', 'password', '');
    }

    public function testIfUserIdCanBeAssignedToEveryAssignments(): void
    {
        $assignment1 = new AssignmentDto(Assignment::STATE_READY, 1);
        $assignment2 = new AssignmentDto(Assignment::STATE_READY, 1);

        $user = new UserDto('username', 'password', null, new AssignmentDtoCollection($assignment1, $assignment2));

        $user->assignUserIdForAssignments(3);

        foreach ($user->getAssignments() as $assignment) {
            self::assertSame(3, $assignment->getUserId());
        }
    }
}
