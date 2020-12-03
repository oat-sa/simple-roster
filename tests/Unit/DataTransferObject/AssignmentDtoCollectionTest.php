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
        $assignment = new AssignmentDto('test', 1, 'testUsername', 1);
        $subject = (new AssignmentDtoCollection())->add($assignment);

        self::assertCount(1, $subject);
        self::assertSame($assignment, $subject->getIterator()->current());
    }

    public function testEmptiness(): void
    {
        $subject = new AssignmentDtoCollection();
        self::assertTrue($subject->isEmpty());

        $subject->add(new AssignmentDto('test', 1, 'testUsername', 1));
        self::assertFalse($subject->isEmpty());
    }

    public function testItReturnsUniqueUsernames(): void
    {
        $assignment1 = new AssignmentDto('test', 1, 'testUsername', 1);
        $assignment2 = new AssignmentDto('test', 1, 'testUsername', 1);
        $assignment3 = new AssignmentDto('test', 1, 'testUsername_2', 1);

        $subject = (new AssignmentDtoCollection())
            ->add($assignment1)
            ->add($assignment2)
            ->add($assignment3);

        self::assertSame(['testUsername', 'testUsername_2'], $subject->getAllUsernames());
    }
}
