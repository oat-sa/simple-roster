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

namespace App\Tests\Unit\Lti\Service;

use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Exception\AssignmentNotProcessableException;
use App\Generator\NonceGenerator;
use App\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use App\Lti\Service\GetUserAssignmentLtiRequestService;
use App\Security\OAuth\OAuthSigner;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class GetUserAssignmentLtiRequestServiceTest extends TestCase
{
    /** @var GetUserAssignmentLtiRequestService */
    private $subject;

    /** @var LtiInstanceLoadBalancerInterface|MockObject */
    private $loadBalancer;

    /** @var string */
    private $ltiLaunchPresentationReturnUrl;

    /** @var string */
    private $ltiLaunchPresentationLocale;

    /** @var bool */
    private $ltiInstancesLoadBalancerEnabled;

    public function setUp(): void
    {
        parent::setUp();

        $this->ltiLaunchPresentationReturnUrl = 'http://example.com/index.html';
        $this->ltiLaunchPresentationLocale = 'fr-FR';
        $this->ltiInstancesLoadBalancerEnabled = true;
        $this->loadBalancer = $this->createMock(LtiInstanceLoadBalancerInterface::class);

        $this->subject = new GetUserAssignmentLtiRequestService(
            $this->createMock(OAuthSigner::class),
            $this->createMock(NonceGenerator::class),
            $this->createMock(RouterInterface::class),
            $this->loadBalancer,
            $this->ltiLaunchPresentationReturnUrl,
            $this->ltiLaunchPresentationLocale,
            $this->ltiInstancesLoadBalancerEnabled
        );
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

        $expectedLtiContextId = 'expectedLtiContextId';

        $this->loadBalancer
            ->expects(self::once())
            ->method('getLtiRequestContextId')
            ->with($user)
            ->willReturn($expectedLtiContextId);

        self::assertSame(
            [
                'ltiLink' => '/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL3Rlc3QtZGVsaXZlcnktdXJpLmh0bWwifQ==',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'testLtiKey',
                    'oauth_nonce' => '',
                    'oauth_signature' => '',
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => (string)Carbon::now()->timestamp,
                    'oauth_version' => '1.0',
                    'lti_message_type' => 'basic-lti-launch-request',
                    'lti_version' => 'LTI-1p0',
                    'context_id' => $expectedLtiContextId,
                    'roles' => 'Learner',
                    'user_id' => 'testUsername',
                    'lis_person_name_full' => 'testUsername',
                    'resource_link_id' => 5,
                    'lis_outcome_service_url' => null,
                    'lis_result_sourcedid' => 5,
                    'launch_presentation_return_url' => $this->ltiLaunchPresentationReturnUrl,
                    'launch_presentation_locale' => $this->ltiLaunchPresentationLocale,
                ],
            ],
            $this->subject->getAssignmentLtiRequest($assignment)->jsonSerialize()
        );

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
