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

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Exception\RegistrationNotFoundException;
use OAT\SimpleRoster\Lti\Factory\Lti1p3RequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class Lti1p3RequestFactoryTest extends TestCase
{
    /** @var Lti1p3RequestFactory */
    private $subject;

    /** @var LtiResourceLinkLaunchRequestBuilder|MockObject */
    private $ltiRequestBuilder;

    /** @var RegistrationRepositoryInterface|MockObject */
    private $registrationRepository;

    /** @var RouterInterface|MockObject */
    private $router;

    /** @var LtiConfiguration|MockObject */
    private $ltiConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiRequestBuilder = $this->createMock(LtiResourceLinkLaunchRequestBuilder::class);
        $this->registrationRepository = $this->createMock(RegistrationRepositoryInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->ltiConfiguration = $this->createMock(LtiConfiguration::class);

        $this->subject = new Lti1p3RequestFactory(
            $this->registrationRepository,
            $this->ltiRequestBuilder,
            $this->router,
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

        $assignment = $this->createAssignmentMock($maxAttempts, $attemptsCount, $assignmentStatus);

        $this->createRegistrationMock();

        $message = $this->createMock(LtiMessageInterface::class);
        $message
            ->expects(self::once())
            ->method('toUrl')
            ->willReturn('link');

        $this->ltiRequestBuilder
            ->expects(self::once())
            ->method('buildLtiResourceLinkLaunchRequest')
            ->willReturn($message);

        $this->router
            ->expects(self::once())
            ->method('generate')
            ->willReturn('ltiOutcomeUri');

        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiRegistrationId')
            ->willReturn('registrationId');

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

    public function testShouldThrowRegistrationNotFoundException(): void
    {
        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiRegistrationId')
            ->willReturn('registrationId');

        $this->expectException(RegistrationNotFoundException::class);
        $this->expectExceptionMessage('Registration registrationId not found.');

        $assignment = $this->createAssignmentMock(5, 1, Assignment::STATE_READY);

        $this->subject->create($assignment);
    }

    private function createAssignmentMock(int $maxAttempts, int $attemptsCount, string $assignmentStatus): Assignment
    {
        $lineItem = (new LineItem())
            ->setMaxAttempts($maxAttempts)
            ->setUri('http://test-delivery-uri.html')
            ->setSlug('slug')
            ->setLabel('label');

        $user = (new User())
            ->setUsername('testUsername')
            ->setGroupId('groupId');

        $assignment = $this->createPartialMock(Assignment::class, ['getId']);

        $assignment
            ->method('getId')
            ->willReturn(5);

        return $assignment
            ->setLineItem($lineItem)
            ->setUser($user)
            ->setAttemptsCount($attemptsCount)
            ->setState($assignmentStatus);
    }

    private function createRegistrationMock(): void
    {
        $registration = $this->createMock(RegistrationInterface::class);

        $this->registrationRepository
            ->expects(self::once())
            ->method('find')
            ->willReturn($registration);
    }
}
