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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

namespace OAT\SimpleRoster\Tests\Unit\Service\LineItem;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Service\LineItem\LineItemAssignedIndexResolver;
use PHPUnit\Framework\TestCase;

class LineItemAssignedIndexResolverTest extends TestCase
{
    public function testEmptyRepository(): void
    {
        $repositoryMock = self::createMock(AssignmentRepository::class);
        $repositoryMock->method('findByLineItemId')->willReturn(null);
        $service = new LineItemAssignedIndexResolver($repositoryMock);

        $result = $service->getLastUserAssignedToLineItems([(new LineItem())->setSlug('test_slug1')]);

        self::assertEquals(['test_slug1' => 0], $result);
    }

    public function testHasGeneratedUsers(): void
    {
        $assignment = new Assignment();
        $assignment->setUser((new User())->setUsername('test_777'));

        $repositoryMock = self::createMock(AssignmentRepository::class);
        $repositoryMock->method('findByLineItemId')->willReturn($assignment);
        $service = new LineItemAssignedIndexResolver($repositoryMock);

        $result = $service->getLastUserAssignedToLineItems([(new LineItem())->setSlug('test_slug1')]);

        self::assertEquals(['test_slug1' => 777], $result);
    }
}
