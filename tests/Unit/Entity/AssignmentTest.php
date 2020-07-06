<?php

declare(strict_types=1);

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

namespace App\Tests\Unit\Entity;

use App\Entity\Assignment;
use App\Entity\LineItem;
use PHPUnit\Framework\TestCase;

class AssignmentTest extends TestCase
{
    /** @var LineItem */
    private $lineItem;

    /** @var Assignment */
    private $subject;

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

        $this->assertEquals(
            Assignment::STATE_COMPLETED,
            $this->subject->getState()
        );
    }

    public function testItUpdatesStateAsReadyIfMaxAttemptsIs0(): void
    {
        $this->lineItem->setMaxAttempts(0);

        $this->subject->incrementAttemptsCount();

        $this->subject->complete();

        $this->assertEquals(
            Assignment::STATE_READY,
            $this->subject->getState()
        );
    }

    public function testItUpdatesStateAsReadyIfMaxAttemptsIsNotReached(): void
    {
        $this->lineItem->setMaxAttempts(2);

        $this->subject->incrementAttemptsCount();

        $this->subject->complete();

        $this->assertEquals(
            Assignment::STATE_READY,
            $this->subject->getState()
        );
    }
}
