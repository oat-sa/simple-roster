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

use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Exception\UserNotFoundException;
use OAT\SimpleRoster\Ingester\AssignmentIngester;
use OAT\SimpleRoster\Repository\NativeAssignmentRepository;
use OAT\SimpleRoster\Repository\NativeUserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class AssignmentIngesterTest extends TestCase
{
    /** @var NativeUserRepository|MockObject */
    private $userRepository;

    /** @var NativeAssignmentRepository|MockObject */
    private $assignmentRepository;

    /** @var AssignmentIngester */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(NativeUserRepository::class);
        $this->assignmentRepository = $this->createMock(NativeAssignmentRepository::class);

        $this->subject = new AssignmentIngester($this->userRepository, $this->assignmentRepository);
    }

    public function testItThrowsExceptionIfUserIsNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with username 'nonExistingUser' cannot not found.");

        $assignment = new AssignmentDto(
            Assignment::STATE_READY,
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'nonExistingUser'
        );

        $assignmentCollection = new AssignmentDtoCollection(...[$assignment]);

        $this->subject->ingest($assignmentCollection);
    }

    public function testItCanIngestAssignments(): void
    {
        $lineItemId = new UuidV6('00000001-0000-6000-0000-000000000000');

        $assignment1 = new AssignmentDto(Assignment::STATE_READY, $lineItemId, 'testUser1');
        $assignment2 = new AssignmentDto(Assignment::STATE_READY, $lineItemId, 'testUser2');
        $assignmentCollection = new AssignmentDtoCollection(...[$assignment1, $assignment2]);

        $this->userRepository
            ->expects(self::once())
            ->method('findUsernames')
            ->with(['testUser1', 'testUser2'])
            ->willReturn([
                ['id' => 1, 'username' => 'testUser1'],
                ['id' => 2, 'username' => 'testUser2'],
            ]);

        $this->assignmentRepository
            ->expects(self::once())
            ->method('insertMultiple')
            ->with($assignmentCollection);

        $this->subject->ingest($assignmentCollection);

        self::assertSame(1, $assignment1->getUserId());
        self::assertSame(2, $assignment2->getUserId());
    }
}
