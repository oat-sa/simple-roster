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
use OAT\SimpleRoster\Lti\Exception\InvalidLtiVersionException;
use OAT\SimpleRoster\Lti\Factory\Lti1p1RequestFactory;
use OAT\SimpleRoster\Lti\Factory\Lti1p3RequestFactory;
use OAT\SimpleRoster\Lti\Factory\LtiRequestFactory;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LtiRequestFactoryTest extends TestCase
{
    /** @var Lti1p1RequestFactory|MockObject */
    private $lti1p1RequestFactory;

    /** @var Lti1p3RequestFactory|MockObject */
    private $lti1p3RequestFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->lti1p1RequestFactory = $this->createMock(Lti1p1RequestFactory::class);
        $this->lti1p3RequestFactory = $this->createMock(Lti1p3RequestFactory::class);
    }

    public function testShouldCreateLti1p1Request(): void
    {
        $subject = new LtiRequestFactory(
            LtiRequest::LTI_VERSION_1P1,
            $this->lti1p1RequestFactory,
            $this->lti1p3RequestFactory
        );

        $result = $subject->__invoke();

        $this->assertTrue($result instanceof Lti1p1RequestFactory);
    }

    public function testShouldCreateLti1p3Request(): void
    {
        $subject = new LtiRequestFactory(
            LtiRequest::LTI_VERSION_1P3,
            $this->lti1p1RequestFactory,
            $this->lti1p3RequestFactory
        );

        $result = $subject->__invoke();

        $this->assertTrue($result instanceof Lti1p3RequestFactory);
    }

    public function testShouldThrowInvalidLtiVersionException(): void
    {
        $this->expectException(InvalidLtiVersionException::class);
        $this->expectExceptionMessage('Invalid LTI Version specified: InvalidVersion');

        $subject = new LtiRequestFactory(
            'InvalidVersion',
            $this->lti1p1RequestFactory,
            $this->lti1p3RequestFactory
        );

        $subject->__invoke();
    }
}
