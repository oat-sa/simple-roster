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

namespace OAT\SimpleRoster\Tests\Unit\Lti\Builder;

use LogicException;
use OAT\SimpleRoster\Lti\Builder\LtiRequestFactoryBuilder;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Factory\Lti1p1RequestFactory;
use OAT\SimpleRoster\Lti\Factory\Lti1p3RequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LtiRequestFactoryBuilderTest extends TestCase
{
    /** @var LtiRequestFactoryBuilder */
    private LtiRequestFactoryBuilder $subject;

    /** @var Lti1p1RequestFactory */
    private $lti1p1RequestFactory;

    /** @var Lti1p3RequestFactory */
    private $lti1p3RequestFactory;

    /** @var LtiConfiguration|MockObject */
    private $ltiConfiguration;

    public function setUp(): void
    {
        parent::setUp();

        $this->lti1p1RequestFactory = $this->createMock(Lti1p1RequestFactory::class);
        $this->lti1p3RequestFactory = $this->createMock(Lti1p3RequestFactory::class);
        $this->ltiConfiguration = $this->createMock(LtiConfiguration::class);

        $this->subject = new LtiRequestFactoryBuilder(
            $this->lti1p1RequestFactory,
            $this->lti1p3RequestFactory,
            $this->ltiConfiguration
        );
    }

    public function testShouldReturnLti1p1RequestFactory(): void
    {
        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiVersion')
            ->willReturn(LtiRequest::LTI_VERSION_1P1);

        self::assertSame($this->lti1p1RequestFactory, $this->subject->__invoke());
    }

    public function testShouldReturnLti1p3RequestFactory(): void
    {
        $this->ltiConfiguration
            ->expects(self::once())
            ->method('getLtiVersion')
            ->willReturn(LtiRequest::LTI_VERSION_1P3);

        self::assertSame($this->lti1p3RequestFactory, $this->subject->__invoke());
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
