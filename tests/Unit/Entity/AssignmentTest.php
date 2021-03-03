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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Entity;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use PHPUnit\Framework\TestCase;

class AssignmentTest extends TestCase
{
    public function testItUpdatesStateAsCompletedIfMaxAttemptsIsReached(): void
    {
        $lineItem = new LineItem(1, 'testLabel', 'testUri', 'testSlug', LineItem::STATUS_ENABLED, 1);
        $subject = new Assignment();
        $subject->setLineItem($lineItem);

        $subject->incrementAttemptsCount();

        $subject->complete();

        self::assertSame(
            Assignment::STATE_COMPLETED,
            $subject->getState()
        );
    }

    public function testItUpdatesStateAsReadyIfMaxAttemptsIs0(): void
    {
        $lineItem = new LineItem(1, 'testLabel', 'testUri', 'testSlug', LineItem::STATUS_ENABLED);
        $subject = new Assignment();
        $subject->setLineItem($lineItem);

        $subject->incrementAttemptsCount();

        $subject->complete();

        self::assertSame(
            Assignment::STATE_READY,
            $subject->getState()
        );
    }

    public function testItUpdatesStateAsReadyIfMaxAttemptsIsNotReached(): void
    {
        $lineItem = new LineItem(1, 'testLabel', 'testUri', 'testSlug', LineItem::STATUS_ENABLED, 2);
        $subject = new Assignment();
        $subject->setLineItem($lineItem);

        $subject->incrementAttemptsCount();

        $subject->complete();

        self::assertSame(
            Assignment::STATE_READY,
            $subject->getState()
        );
    }
}
