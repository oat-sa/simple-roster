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
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Factory\Lti1p1RequestFactory;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class Lti1p1RequestFactoryTest extends TestCase
{
    /** @var Lti1p1RequestFactory */
    private $subject;

    /** @var LtiInstanceLoadBalancerInterface|MockObject */
    private $loadBalancer;

    /** @var LtiConfiguration|MockObject */
    private $ltiConfiguration;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadBalancer = $this->createMock(LtiInstanceLoadBalancerInterface::class);
        $this->ltiConfiguration = $this->createMock(LtiConfiguration::class);

        $this->subject = new Lti1p1RequestFactory(
            $this->createMock(OAuthSigner::class),
            $this->createMock(NonceGenerator::class),
            $this->createMock(RouterInterface::class),
            $this->loadBalancer,
            $this->ltiConfiguration
        );
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

        $user = (new User())->setUsername('testUsername');

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
            ->with($assignment)
            ->willReturn($expectedLtiContextId);

        $expectedLtiInstance = new LtiInstance(
            1,
            'ltiInstanceLabel',
            'http://lti-infra.taocloud.org',
            'ltiKey',
            'ltiSecret'
        );

        $this->loadBalancer
            ->expects(self::once())
            ->method('getLtiInstance')
            ->with($user)
            ->willReturn($expectedLtiInstance);

        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiLaunchPresentationReturnUrl')
            ->willReturn('http://example.com/index.html');

        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiLaunchPresentationLocale')
            ->willReturn('fr-FR');

        self::assertSame(
            [
                'ltiLink' => 'http://lti-infra.taocloud.org/ltiDeliveryProvider/DeliveryTool/launch/' .
                    'eyJkZWxpdmVyeSI6Imh0dHA6XC9cL3Rlc3QtZGVsaXZlcnktdXJpLmh0bWwifQ==',
                'ltiVersion' => LtiRequest::LTI_VERSION_1P1,
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => $expectedLtiInstance->getLtiKey(),
                    'oauth_nonce' => '',
                    'oauth_signature' => '',
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => (string)Carbon::now()->timestamp,
                    'oauth_version' => '1.0',
                    'lti_message_type' => 'basic-lti-launch-request',
                    'lti_version' => LtiRequest::LTI_VERSION_1P1,
                    'context_id' => $expectedLtiContextId,
                    'roles' => 'Learner',
                    'user_id' => 'testUsername',
                    'lis_person_name_full' => 'testUsername',
                    'resource_link_id' => 5,
                    'lis_outcome_service_url' => null,
                    'lis_result_sourcedid' => 5,
                    'launch_presentation_return_url' => 'http://example.com/index.html',
                    'launch_presentation_locale' => 'fr-FR',
                ],
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
