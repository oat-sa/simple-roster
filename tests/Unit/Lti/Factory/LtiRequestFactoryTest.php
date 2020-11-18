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

use LogicException;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Factory\Lti1p1RequestFactory;
use OAT\SimpleRoster\Lti\Factory\Lti1p3RequestFactory;
use OAT\SimpleRoster\Lti\Factory\LtiRequestFactory;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

class LtiRequestFactoryTest extends TestCase
{
    /** @var LtiRequestFactory */
    private $subject;

    /** @var LtiConfiguration|MockObject */
    private $ltiConfiguration;

    public function setUp(): void
    {
        parent::setUp();

        $this->ltiConfiguration = $this->createMock(LtiConfiguration::class);

        $this->subject = new LtiRequestFactory(
            $this->ltiConfiguration,
            $this->createMock(OAuthSigner::class),
            $this->createMock(NonceGenerator::class),
            $this->createMock(RouterInterface::class),
            $this->createMock(LtiInstanceLoadBalancerInterface::class),
            $this->createMock(RegistrationRepositoryInterface::class),
            $this->createMock(LtiResourceLinkLaunchRequestBuilder::class)
        );
    }

    public function testShouldReturnLti1p1RequestFactory(): void
    {
        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiVersion')
            ->willReturn(LtiRequest::LTI_VERSION_1P1);

        $result = $this->subject->__invoke();

        self::assertTrue($result instanceof Lti1p1RequestFactory);
    }

    public function testShouldReturnLti1p3RequestFactory(): void
    {
        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiVersion')
            ->willReturn(LtiRequest::LTI_VERSION_1P3);

        $result = $this->subject->__invoke();

        self::assertTrue($result instanceof Lti1p3RequestFactory);
    }

    public function testShouldThrowLogicExceptionWhenVersionIsInvalid(): void
    {
        $this->ltiConfiguration
            ->expects(self::exactly(2))
            ->method('getLtiVersion')
            ->willReturn('InvalidVersion');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid LTI Version specified: InvalidVersion');

        $this->subject->__invoke();
    }
}
