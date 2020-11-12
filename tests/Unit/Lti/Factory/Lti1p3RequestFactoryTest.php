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

namespace OAT\SimpleRoster\Tests\Unit\Lti\Factory;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Factory\Lti1p3RequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class Lti1p3RequestFactoryTest extends TestCase
{
    /** @var Lti1p3RequestFactory */
    private $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new Lti1p3RequestFactory();
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

        self::assertSame(
            [
                'ltiLink' => 'link',
                'ltiVersion' => LtiRequest::LTI_VERSION_1P3,
                'ltiParams' => [],
            ],
            $this->subject->create($assignment)->jsonSerialize()
        );

        Carbon::setTestNow();
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
