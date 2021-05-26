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

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Lti\Builder\Lti1p3MessageBuilder;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Exception\RegistrationNotFoundException;
use OAT\SimpleRoster\Lti\Factory\Lti1p3RequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class Lti1p3RequestFactoryTest extends TestCase
{
    private Lti1p3RequestFactory $subject;

    /** @var Lti1p3MessageBuilder|MockObject */
    private $ltiMessageBuilder;

    /** @var RegistrationRepositoryInterface|MockObject */
    private $registrationRepository;

    /** @var LtiConfiguration|MockObject */
    private $ltiConfiguration;

    /** @var RouterInterface|MockObject */
    private $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiMessageBuilder = $this->createMock(Lti1p3MessageBuilder::class);
        $this->registrationRepository = $this->createMock(RegistrationRepositoryInterface::class);
        $this->ltiConfiguration = $this->createMock(LtiConfiguration::class);
        $this->router = $this->createMock(RouterInterface::class);

        $this->subject = new Lti1p3RequestFactory(
            $this->registrationRepository,
            $this->ltiMessageBuilder,
            $this->ltiConfiguration,
            $this->router
        );
    }

    public function testShouldThrowRegistrationNotFoundException(): void
    {
        $this->expectException(RegistrationNotFoundException::class);
        $this->expectExceptionMessage('Registration registrationId not found.');

        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiRegistrationId')
            ->willReturn('registrationId');

        $this->registrationRepository
            ->expects(self::once())
            ->method('find')
            ->with('registrationId')
            ->willReturn(null);

        $this->subject->create(new Assignment());
    }

    public function testItReturnsAssignmentLtiRequest(): void
    {
        $expectedLtiLinkUrl = 'http://expected.url';

        $this->registrationRepository
            ->method('find')
            ->willReturn($this->createMock(Registration::class));


        $assignment = $this->createMock(Assignment::class);

        $message = $this->createMock(LtiMessageInterface::class);
        $message
            ->expects(self::once())
            ->method('toUrl')
            ->willReturn($expectedLtiLinkUrl);

        $this->ltiMessageBuilder
            ->expects(self::exactly(3))
            ->method('withMessagePayloadClaim')
            ->willReturn($this->ltiMessageBuilder);

        $this->ltiMessageBuilder
            ->expects(self::once())
            ->method('build')
            ->willReturn($message);

        $this->router
            ->expects(self::once())
            ->method('generate')
            ->willReturn($expectedLtiLinkUrl);

        $request = $this->subject->create($assignment);

        self::assertSame($expectedLtiLinkUrl, $request->getLink());
        self::assertSame(LtiRequest::LTI_VERSION_1P3, $request->getVersion());
        self::assertSame([], $request->getParameters());
    }
}
