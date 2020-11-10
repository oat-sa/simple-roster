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

namespace App\Tests\Unit\Lti\Factory;

use App\Entity\Assignment;
use App\Lti\Exception\InvalidLtiVersionException;
use App\Lti\Factory\Lti1p1RequestFactory;
use App\Lti\Factory\Lti1p3RequestFactory;
use App\Lti\Factory\LtiRequestFactory;
use App\Lti\Request\LtiRequest;
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

        $assignment = new Assignment();

        $this->lti1p1RequestFactory
            ->expects($this->once())
            ->method('create')
            ->with($assignment);

        $this->lti1p3RequestFactory
            ->expects($this->never())
            ->method('create');

        $result = $subject->create($assignment);

        $this->assertTrue($result instanceof LtiRequest);
    }

    public function testShouldCreateLti1p3Request(): void
    {
        $subject = new LtiRequestFactory(
            LtiRequest::LTI_VERSION_1P3,
            $this->lti1p1RequestFactory,
            $this->lti1p3RequestFactory
        );

        $assignment = new Assignment();

        $this->lti1p1RequestFactory
            ->expects($this->never())
            ->method('create');

        $this->lti1p3RequestFactory
            ->expects($this->once())
            ->method('create')
            ->with($assignment);

        $result = $subject->create($assignment);

        $this->assertTrue($result instanceof LtiRequest);
    }

    public function testShouldThrowInvalidLtiVersionException()
    {
        $this->expectException(InvalidLtiVersionException::class);
        $this->expectExceptionMessage('Invalid LTI Version specified: InvalidVersion');

        $subject = new LtiRequestFactory(
            'InvalidVersion',
            $this->lti1p1RequestFactory,
            $this->lti1p3RequestFactory
        );

        $subject->create(new Assignment());
    }
}
