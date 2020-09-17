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
use App\DataTransferObject\AssignmentDtoCollection;
use Countable;
use IteratorAggregate;
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
        $assignment = new AssignmentDto(1, 'test', 1, 1);
        $subject = (new AssignmentDtoCollection())->add($assignment);

        self::assertCount(1, $subject);
        self::assertSame($assignment, $subject->getIterator()->current());
    }
}
