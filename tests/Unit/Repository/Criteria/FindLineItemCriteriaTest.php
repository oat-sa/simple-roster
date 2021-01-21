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

namespace OAT\SimpleRoster\Tests\Unit\Repository\Criteria;

use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use PHPUnit\Framework\TestCase;

class FindLineItemCriteriaTest extends TestCase
{
    public function testFindLineItemCriteria(): void
    {
        $subject = new FindLineItemCriteria();

        self::assertFalse($subject->hasLineItemSlugsCriteria());
        self::assertFalse($subject->hasLineItemIdsCriteria());

        $subject->addLineItemSlugs('slug1', 'slug2', 'slug3');
        $subject->addLineItemIds(1, 2, 3);

        self::assertTrue($subject->hasLineItemIdsCriteria());
        self::assertSame([1, 2, 3], $subject->getLineItemIds());

        self::assertTrue($subject->hasLineItemSlugsCriteria());
        self::assertSame(['slug1', 'slug2', 'slug3'], $subject->getLineItemSlugs());
    }
}
