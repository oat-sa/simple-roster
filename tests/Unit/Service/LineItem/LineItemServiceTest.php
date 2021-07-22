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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\LineItem;

use OAT\SimpleRoster\Model\LineItemCollection;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\ResultSet\LineItemResultSet;
use OAT\SimpleRoster\Service\LineItem\LineItemService;
use OAT\SimpleRoster\Service\LineItem\ListLineItemResponse;
use PHPUnit\Framework\TestCase;

class LineItemServiceTest extends TestCase
{
    public function testListLineItems(): void
    {
        $lineItemRepository = $this->createMock(LineItemRepository::class);

        $criteria = new FindLineItemCriteria();
        $lineItemCollection = new LineItemCollection();
        $lineItemResultSet = new LineItemResultSet($lineItemCollection, false, null);

        $subject = new LineItemService($lineItemRepository);

        $lineItemRepository
            ->expects(self::once())
            ->method('findLineItemsByCriteria')
            ->with($criteria, 10, null)
            ->willReturn($lineItemResultSet);

        $result = $subject->listLineItems($criteria, 10, null);

        self::assertSame(ListLineItemResponse::class, get_class($result));
    }
}
