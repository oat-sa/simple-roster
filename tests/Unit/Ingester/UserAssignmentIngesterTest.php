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

namespace OAT\SimpleRoster\Tests\Unit\Ingester;

use OAT\SimpleRoster\Ingester\UserAssignmentIngester;
use OAT\SimpleRoster\Ingester\AssignmentIngester;
use OAT\SimpleRoster\Repository\NativeUserRepository;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use phpDocumentor\Reflection\Types\Void_;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserAssignmentIngesterTest extends TestCase
{
    /** @var NativeUserRepository|MockObject */
    private $userRepository;

    /** @var NativeAssignmentRepository|MockObject */
    private $assignmentIngester;

    private UserAssignmentIngester $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(NativeUserRepository::class);
        $this->assignmentIngester = $this->createMock(AssignmentIngester::class);
        $this->userPasswordHasher = $this->createMock(UserPasswordHasher::class);

        $this->subject = new UserAssignmentIngester(
            $this->userPasswordHasher,
            $this->userRepository,
            $this->assignmentIngester
        );
    }

    public function testItCreateUserDtoCollection(): void
    {
        $this->userPasswordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with(new User(), 'password1')
            ->willReturn('hashed_password');

        $userDtoCollection = new UserDtoCollection();
        $output = $this->subject->createUserDtoCollection($userDtoCollection, "user1", "password1", "group_1");
        self::assertCount(1, $output);
        self::assertFalse($output->isEmpty());
    }

    public function testItCreateAssignmentDtoCollection(): void
    {
        $assignmentDtoCollection = new AssignmentDtoCollection();
        $output = $this->subject->createAssignmentDtoCollection($assignmentDtoCollection, 1, "user1");
        self::assertCount(1, $output);
        self::assertFalse($output->isEmpty());
    }

    public function testItCanSaveBulkUserAssignmentData(): void
    {
        $user1 = new UserDto('testUser1', 'testPassword1', 'testGroup1');
        $user2 = new UserDto('testUser2', 'testPassword2', 'testGroup2');
        $userCollection = (new UserDtoCollection())->add($user1)->add($user2);

        $this->userRepository->expects(self::once())->method('insertMultiple')->with($userCollection);

        $assignment1 = new AssignmentDto(Assignment::STATE_READY, 1, 'testUser1');
        $assignment2 = new AssignmentDto(Assignment::STATE_READY, 1, 'testUser2');
        $assignmentCollection = new AssignmentDtoCollection(...[$assignment1, $assignment2]);
        $this->assignmentIngester->expects(self::once())->method('ingest')->with($assignmentCollection);

        $this->subject->saveBulkUserAssignmentData($userCollection, $assignmentCollection);
    }
}
