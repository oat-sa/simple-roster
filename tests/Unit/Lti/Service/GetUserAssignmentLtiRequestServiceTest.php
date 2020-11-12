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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Lti\Service;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\AssignmentNotProcessableException;
use OAT\SimpleRoster\Lti\Factory\Lti1p1RequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Lti\Service\GetUserAssignmentLtiRequestService;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetUserAssignmentLtiRequestServiceTest extends TestCase
{
    /** @var GetUserAssignmentLtiRequestService */
    private $subject;

    /** @var Lti1p1RequestFactory|MockObject  */
    private $ltiRequestFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->ltiRequestFactory = $this->createMock(Lti1p1RequestFactory::class);

        $this->subject = new GetUserAssignmentLtiRequestService($this->ltiRequestFactory);
    }

    /**
     * @dataProvider provideNonSuitableAssignmentStates
     */
    public function testItThrowsExceptionIfAssignmentDoesNotHaveSuitableState(string $nonSuitableAssignmentStatus): void
    {
        $this->expectException(AssignmentNotProcessableException::class);
        $this->expectExceptionMessage("Assignment with id '5' does not have a suitable state.");

        $lineItem = new LineItem();
        $assignment = $this->createPartialMock(Assignment::class, ['getId']);

        $assignment
            ->method('getId')
            ->willReturn(5);

        $assignment
            ->setLineItem($lineItem)
            ->setState($nonSuitableAssignmentStatus);

        $this->ltiRequestFactory
            ->expects($this->never())
            ->method('create');

        $this->subject->getAssignmentLtiRequest($assignment);
    }

    /**
     * @dataProvider provideInvalidLineItemAssignmentCombinations
     */
    public function testItThrowsExceptionIfAssignmentHasReachedMaximumAttempts(
        int $maxAttempts,
        int $attemptsCount,
        string $assignmentStatus
    ): void {
        $this->expectException(AssignmentNotProcessableException::class);
        $this->expectExceptionMessage("Assignment with id '8' has reached the maximum attempts.");

        $lineItem = (new LineItem())
            ->setMaxAttempts($maxAttempts);

        $assignment = $this->createPartialMock(Assignment::class, ['getId']);

        $assignment
            ->method('getId')
            ->willReturn(8);

        $assignment
            ->setLineItem($lineItem)
            ->setState($assignmentStatus)
            ->setAttemptsCount($attemptsCount);

        $this->ltiRequestFactory
            ->expects($this->never())
            ->method('create');

        $this->subject->getAssignmentLtiRequest($assignment);
    }

    /**
     * @dataProvider provideValidLineItemAssignmentCombinations
     */
    public function testItReturnsAssignmentLtiRequest(
        int $maxAttempts,
        int $attemptsCount,
        string $assignmentStatus
    ): void {
        Carbon::setTestNow(Carbon::createFromDate(2020, 1, 1));

        $lineItem = (new LineItem())
            ->setMaxAttempts($maxAttempts)
            ->setUri('http://test-delivery-uri.html');

        $user = (new User())
            ->setUsername('testUsername');

        $assignment = $this->createPartialMock(Assignment::class, ['getId']);

        $assignment
            ->method('getId')
            ->willReturn(5);

        $assignment
            ->setLineItem($lineItem)
            ->setUser($user)
            ->setAttemptsCount($attemptsCount)
            ->setState($assignmentStatus);

        $this->ltiRequestFactory
            ->expects($this->once())
            ->method('create')
            ->with($assignment);

        $ltiRequest = $this->subject->getAssignmentLtiRequest($assignment);

        $this->assertTrue($ltiRequest instanceof LtiRequest);

        Carbon::setTestNow();
    }

    public function provideNonSuitableAssignmentStates(): array
    {
        return [
            Assignment::STATE_CANCELLED => [Assignment::STATE_CANCELLED],
            Assignment::STATE_COMPLETED => [Assignment::STATE_COMPLETED],
        ];
    }

    public function provideInvalidLineItemAssignmentCombinations(): array
    {
        return [
            'withAllAttemptsTakenAndCompletedStatus' => [
                'maxAttempts' => 4,
                'attemptsCount' => 4,
                'assignmentState' => Assignment::STATE_COMPLETED,
            ],
            'withTooManyAttemptsAndReadyStatus' => [
                'maxAttempts' => 4,
                'attemptsCount' => 5,
                'assignmentState' => Assignment::STATE_READY,
            ],
            'withTooManyAttemptsAndStartedStatus' => [
                'maxAttempts' => 4,
                'attemptsCount' => 5,
                'assignmentState' => Assignment::STATE_STARTED,
            ],
        ];
    }

    public function provideValidLineItemAssignmentCombinations(): array
    {
        return [
            'withMaxAttemptsSpecifiedAndAvailableAttemptsAndReadyStatus' => [
                'maxAttempts' => 2,
                'attemptsCount' => 1,
                'assignmentState' => Assignment::STATE_READY,
            ],
            'withMaxAttemptsSpecifiedAndAvailableAttemptsAndStartedStatus' => [
                'maxAttempts' => 2,
                'attemptsCount' => 1,
                'assignmentState' => Assignment::STATE_STARTED,
            ],
            'withMaxAttemptsSpecifiedAndLastAttemptStartedAndStartedStatus' => [
                'maxAttempts' => 3,
                'attemptsCount' => 3,
                'assignmentState' => Assignment::STATE_STARTED,
            ],
            'withNoMaxAttemptsSpecifiedAndNoAttemptsTakenAndReadyStatus' => [
                'maxAttempts' => 0,
                'attemptsCount' => 0,
                'assignmentState' => Assignment::STATE_READY,
            ],
            'withNoMaxAttemptsSpecifiedAndSomeAttemptsTakenAndReadyStatus' => [
                'maxAttempts' => 0,
                'attemptsCount' => 4,
                'assignmentState' => Assignment::STATE_READY,
            ],
            'withNoMaxAttemptsSpecifiedAndSomeAttemptsTakenAndStartedStatus' => [
                'maxAttempts' => 0,
                'attemptsCount' => 4,
                'assignmentState' => Assignment::STATE_STARTED,
            ],
        ];
    }
}
