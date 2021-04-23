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

use Carbon\Carbon;
use DateTime;
use InvalidArgumentException;
use LogicException;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\InvalidAssignmentStatusTransitionException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class AssignmentTest extends TestCase
{
    public function testItThrowsExceptionIfInvalidStatusReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid assignment status received: 'invalidStatus'.");

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'testUri',
            'testSlug',
            LineItem::STATUS_ENABLED,
            1
        );

        new Assignment(new UuidV6('00000001-0000-6000-0000-000000000000'), 'invalidStatus', $lineItem);
    }

    public function testItThrowsExceptionIAttemptsCountIsSmallerThanZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'attemptsCount' received.");

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED
        );

        new Assignment(new UuidV6('00000001-0000-6000-0000-000000000000'), Assignment::STATUS_READY, $lineItem, -1);
    }

    public function testItThrowsExceptionIfAttemptsCountIsGreaterThanAllowedByLineItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid 'attemptsCount' received.");

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED,
            5
        );

        new Assignment(new UuidV6('00000001-0000-6000-0000-000000000000'), Assignment::STATUS_READY, $lineItem, 6);
    }

    public function testItThrowsExceptionIfUserIsNotSet(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('User is not set');

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED,
            5
        );

        $subject = new Assignment(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            Assignment::STATUS_READY,
            $lineItem
        );

        $subject->getUser();
    }

    /**
     * @dataProvider provideAvailabilityContext
     */
    public function testAvailability(
        string $assignmentStatus,
        string $lineItemStatus,
        ?string $lineItemStartDateTime,
        ?string $lineItemEndDateTime,
        string $currentDateTime,
        bool $expectedAvailability
    ): void {
        Carbon::setTestNow($currentDateTime);

        $lineItemStartDateTime = $lineItemStartDateTime ? new DateTime($lineItemStartDateTime) : null;
        $lineItemEndDateTime = $lineItemEndDateTime ? new DateTime($lineItemEndDateTime) : null;

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            $lineItemStatus,
            0,
            'group',
            $lineItemStartDateTime,
            $lineItemEndDateTime
        );

        $subject = new Assignment(new UuidV6('00000001-0000-6000-0000-000000000000'), $assignmentStatus, $lineItem, 0);

        self::assertSame($expectedAvailability, $subject->isAvailable());

        Carbon::setTestNow();
    }

    public function provideAvailabilityContext(): array
    {
        return [
            'assignmentIsCancelled' => [
                'assignmentStatus' => Assignment::STATUS_CANCELLED,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'lineItemStartDateTime' => null,
                'lineItemEndDateTime' => null,
                'currentDateTime' => '2021-03-01 00:00:00',
                'expectedAvailability' => false,
            ],
            'lineItemIsDisabled' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_DISABLED,
                'lineItemStartDateTime' => null,
                'lineItemEndDateTime' => null,
                'currentDateTime' => '2021-03-01 00:00:00',
                'expectedAvailability' => false,
            ],
            'currentDateIsTooEarly' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'lineItemStartDateTime' => '2021-03-01 00:00:01',
                'lineItemEndDateTime' => null,
                'currentDateTime' => '2021-03-01 00:00:00',
                'expectedAvailability' => false,
            ],
            'currentDateIsTooLate' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'lineItemStartDateTime' => null,
                'lineItemEndDateTime' => '2021-03-01 12:00:00',
                'currentDateTime' => '2021-03-01 12:00:01',
                'expectedAvailability' => false,
            ],
            'isAvailable' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'lineItemStartDateTime' => '2021-03-01 08:00:00',
                'lineItemEndDateTime' => '2021-03-01 12:00:00',
                'currentDateTime' => '2021-03-01 11:00:00',
                'expectedAvailability' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideStartStateTransitionContext
     */
    public function testStartStateTransition(
        string $assignmentStatus,
        string $lineItemStatus,
        int $assignmentAttemptsCount,
        int $lineItemMaxAttemptsCount,
        int $expectedAssignmentAttemptsCount,
        string $expectedExceptionMessage = null
    ): void {
        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            $lineItemStatus,
            $lineItemMaxAttemptsCount
        );

        $subject = new Assignment(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            $assignmentStatus,
            $lineItem,
            $assignmentAttemptsCount
        );

        try {
            $subject->start();

            // If there is no exception, assert on positive use case
            self::assertSame(Assignment::STATUS_STARTED, $subject->getStatus());
        } catch (InvalidAssignmentStatusTransitionException $exception) {
            self::assertSame($expectedExceptionMessage, $exception->getMessage());
        } finally {
            self::assertSame($expectedAssignmentAttemptsCount, $subject->getAttemptsCount());
        }
    }

    public function provideStartStateTransitionContext(): array
    {
        return [
            'assignmentIsStarted' => [
                'assignmentStatus' => Assignment::STATUS_STARTED,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentAttemptsCount' => 1,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "started due to invalid status: 'ready' expected, 'started' detected.",
            ],
            'assignmentIsCancelled' => [
                'assignmentStatus' => Assignment::STATUS_CANCELLED,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentAttemptsCount' => 1,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "started due to invalid status: 'ready' expected, 'cancelled' detected.",
            ],
            'assignmentIsCompleted' => [
                'assignmentStatus' => Assignment::STATUS_COMPLETED,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentAttemptsCount' => 1,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "started due to invalid status: 'ready' expected, 'completed' detected.",
            ],
            'lineItemIsDisabled' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_DISABLED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentAttemptsCount' => 1,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "started, line item is disabled.",
            ],
            'maximumAllowedAttemptsAreReached' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'assignmentAttemptsCount' => 2,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentAttemptsCount' => 2,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "started. Maximum number of attempts (2) have been reached.",
            ],
            'successfulStart' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'lineItemStatus' => LineItem::STATUS_ENABLED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentAttemptsCountAfterStart' => 2,
                'expectedExceptionMessage' => null,
            ],
        ];
    }

    /**
     * @dataProvider provideCancelStateTransitionContext
     */
    public function testCancelStateTransition(
        string $assignmentStatus,
        bool $expectedIsCancellable,
        string $expectedExceptionMessage = null
    ): void {
        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED
        );

        $subject = new Assignment(new UuidV6('00000001-0000-6000-0000-000000000000'), $assignmentStatus, $lineItem);

        if (null !== $expectedExceptionMessage) {
            $this->expectException(InvalidAssignmentStatusTransitionException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        self::assertSame($expectedIsCancellable, $subject->isCancellable());

        $subject->cancel();

        self::assertSame(Assignment::STATUS_CANCELLED, $subject->getStatus());
    }

    public function provideCancelStateTransitionContext(): array
    {
        return [
            'readyAssignment' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'expectedIsCancellable' => true,
                'expectedExceptionMessage' => null,
            ],
            'startedAssignment' => [
                'assignmentStatus' => Assignment::STATUS_STARTED,
                'expectedIsCancellable' => true,
                'expectedExceptionMessage' => null,
            ],
            'cancelledAssignment' => [
                'assignmentStatus' => Assignment::STATUS_CANCELLED,
                'expectedIsCancellable' => false,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "cancelled. Status must be one of 'ready', 'started', 'cancelled' detected.",
            ],
            'completedAssignment' => [
                'assignmentStatus' => Assignment::STATUS_COMPLETED,
                'expectedIsCancellable' => false,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "cancelled. Status must be one of 'ready', 'started', 'completed' detected.",
            ],
        ];
    }

    /**
     * @dataProvider provideCompleteStateTransitionContext
     */
    public function testCompleteStateTransition(
        string $assignmentStatus,
        int $assignmentAttemptsCount,
        int $lineItemMaxAttemptsCount,
        string $expectedAssignmentStatus,
        string $expectedExceptionMessage = null
    ): void {
        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED,
            $lineItemMaxAttemptsCount
        );

        $subject = new Assignment(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            $assignmentStatus,
            $lineItem,
            $assignmentAttemptsCount
        );

        if (null !== $expectedExceptionMessage) {
            $this->expectException(InvalidAssignmentStatusTransitionException::class);
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $subject->complete();

        self::assertSame($expectedAssignmentStatus, $subject->getStatus());
    }

    public function provideCompleteStateTransitionContext(): array
    {
        return [
            'startedAssignmentWithAvailableAttemptsLeft' => [
                'assignmentStatus' => Assignment::STATUS_STARTED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentStatus' => Assignment::STATUS_READY,
                'expectedExceptionMessage' => null,
            ],
            'startedAssignmentWithLastAttemptTaken' => [
                'assignmentStatus' => Assignment::STATUS_STARTED,
                'assignmentAttemptsCount' => 2,
                'lineItemMaxAttemptsCount' => 2,
                'expectedAssignmentStatus' => Assignment::STATUS_COMPLETED,
                'expectedExceptionMessage' => null,
            ],
            'startedAssignmentWithInfiniteAvailableAttempts' => [
                'assignmentStatus' => Assignment::STATUS_STARTED,
                'assignmentAttemptsCount' => 159,
                'lineItemMaxAttemptsCount' => 0, // Infinite attempts
                'expectedAssignmentStatus' => Assignment::STATUS_READY,
                'expectedExceptionMessage' => null,
            ],
            'readyAssignment' => [
                'assignmentStatus' => Assignment::STATUS_READY,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 1,
                'expectedAssignmentStatus' => Assignment::STATUS_READY,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "completed, because it's in 'ready' status, 'started' expected.",
            ],
            'cancelledAssignment' => [
                'assignmentStatus' => Assignment::STATUS_CANCELLED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 1,
                'expectedAssignmentStatus' => Assignment::STATUS_CANCELLED,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "completed, because it's in 'cancelled' status, 'started' expected.",
            ],
            'completedAssignment' => [
                'assignmentStatus' => Assignment::STATUS_COMPLETED,
                'assignmentAttemptsCount' => 1,
                'lineItemMaxAttemptsCount' => 1,
                'expectedAssignmentStatus' => Assignment::STATUS_COMPLETED,
                'expectedExceptionMessage' => "Assignment with id = '00000001-0000-6000-0000-000000000000' cannot be " .
                    "completed, because it's in 'completed' status, 'started' expected.",
            ],
        ];
    }
}
