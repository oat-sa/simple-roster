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

use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\DataTransferObject\AssignmentDto;
use OAT\SimpleRoster\DataTransferObject\AssignmentDtoCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class AssignmentDtoCollectionTest extends TestCase
{
    public function testItImplementsCountable(): void
    {
        self::assertInstanceOf(Countable::class, new AssignmentDtoCollection());
    }

    public function testItImplementsIteratorAggregate(): void
    {
        self::assertInstanceOf(IteratorAggregate::class, new AssignmentDtoCollection());
    }

    public function testIfAssignmentCanBeAdded(): void
    {
        $assignment = new AssignmentDto(
            new UuidV6('00000002-0000-6000-0000-000000000000'),
            'test',
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testUsername',
            new UuidV6('00000001-0000-6000-0000-000000000000')
        );

        $subject = (new AssignmentDtoCollection())->add($assignment);

        self::assertCount(1, $subject);
        self::assertSame($assignment, $subject->getIterator()->current());
    }

    public function testEmptiness(): void
    {
        $subject = new AssignmentDtoCollection();
        self::assertTrue($subject->isEmpty());

        $subject->add(
            new AssignmentDto(
                new UuidV6('00000002-0000-6000-0000-000000000000'),
                'test',
                new UuidV6('00000001-0000-6000-0000-000000000000'),
                'testUsername',
                new UuidV6('00000001-0000-6000-0000-000000000000')
            )
        );

        self::assertFalse($subject->isEmpty());
    }

    public function testItReturnsUniqueUsernames(): void
    {
        $lineItemId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $userId = new UuidV6('00000001-0000-6000-0000-000000000000');

        $assignmentId1 = new UuidV6('00000011-0000-6000-0000-000000000000');
        $assignment1 = new AssignmentDto($assignmentId1, 'test', $lineItemId, 'testUsername', $userId);

        $assignmentId2 = new UuidV6('00000022-0000-6000-0000-000000000000');
        $assignment2 = new AssignmentDto($assignmentId2, 'test', $lineItemId, 'testUsername', $userId);

        $assignmentId3 = new UuidV6('00000033-0000-6000-0000-000000000000');
        $assignment3 = new AssignmentDto($assignmentId3, 'test', $lineItemId, 'testUsername_2', $userId);

        $subject = (new AssignmentDtoCollection())
            ->add($assignment1)
            ->add($assignment2)
            ->add($assignment3);

        self::assertSame(['testUsername', 'testUsername_2'], $subject->getAllUsernames());
    }
}
