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
    private LineItem $lineItem;
    private Assignment $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lineItem = new LineItem();
        $this->subject = new Assignment();
        $this->subject->setLineItem($this->lineItem);
    }

    public function testItUpdatesStateAsCompletedIfMaxAttemptsIsReached(): void
    {
        $this->lineItem->setMaxAttempts(1);

        $this->subject->incrementAttemptsCount();

        $this->subject->complete();

        self::assertSame(
            Assignment::STATE_COMPLETED,
            $this->subject->getState()
        );
    }

    public function testItUpdatesStateAsReadyIfMaxAttemptsIs0(): void
    {
        $this->lineItem->setMaxAttempts(0);

        $this->subject->incrementAttemptsCount();

        $this->subject->complete();

        self::assertSame(
            Assignment::STATE_READY,
            $this->subject->getState()
        );
    }

    public function testItUpdatesStateAsReadyIfMaxAttemptsIsNotReached(): void
    {
        $this->lineItem->setMaxAttempts(2);

        $this->subject->incrementAttemptsCount();

        $this->subject->complete();

        self::assertSame(
            Assignment::STATE_READY,
            $this->subject->getState()
        );
    }
}
