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

use DateTimeImmutable;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use PHPUnit\Framework\TestCase;

class FindLineItemCriteriaTest extends TestCase
{
    public function testFindLineItemCriteria(): void
    {
        $subject = new FindLineItemCriteria();

        self::assertFalse($subject->hasLineItemIdsCriteria());
        self::assertFalse($subject->hasLineItemSlugsCriteria());
        self::assertFalse($subject->hasLineItemLabelsCriteria());
        self::assertFalse($subject->hasLineItemUrisCriteria());
        self::assertFalse($subject->hasLineItemStartAtCriteria());
        self::assertFalse($subject->hasLineItemEndAtCriteria());

        $date = new DateTimeImmutable();

        $subject->addLineItemIds(1, 2, 3);
        $subject->addLineItemSlugs('slug1', 'slug2', 'slug3');
        $subject->addLineItemLabels('label1', 'label2', 'label3');
        $subject->addLineItemUris('uri1', 'uri2', 'uri3');
        $subject->addLineItemStartAt($date);
        $subject->addLineItemEndAt($date);

        self::assertTrue($subject->hasLineItemIdsCriteria());
        self::assertSame([1, 2, 3], $subject->getLineItemIds());

        self::assertTrue($subject->hasLineItemSlugsCriteria());
        self::assertSame(['slug1', 'slug2', 'slug3'], $subject->getLineItemSlugs());

        self::assertTrue($subject->hasLineItemLabelsCriteria());
        self::assertSame(['label1', 'label2', 'label3'], $subject->getLineItemLabels());

        self::assertTrue($subject->hasLineItemUrisCriteria());
        self::assertSame(['uri1', 'uri2', 'uri3'], $subject->getLineItemUris());

        self::assertTrue($subject->hasLineItemStartAtCriteria());
        self::assertSame($date, $subject->getLineItemStartAt());

        self::assertTrue($subject->hasLineItemEndAtCriteria());
        self::assertSame($date, $subject->getLineItemEndAt());
    }
}
