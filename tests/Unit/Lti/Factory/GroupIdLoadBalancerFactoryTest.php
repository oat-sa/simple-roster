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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Lti\Factory;

use OAT\SimpleRoster\Lti\Factory\GroupIdLoadBalancerFactory;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Exception\LtiInstanceNotFoundException;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;
use OAT\SimpleRoster\Entity\LtiInstance;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupIdLoadBalancerFactoryTest extends TestCase
{
    private GroupIdLoadBalancerFactory $subject;
    private UniqueLtiInstanceCollection $ltiInstanceCollection;

    /** @var LtiInstanceRepository|MockObject */
    private $ltiInstanceRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->ltiInstanceRepository = $this->createMock(LtiInstanceRepository::class);
        $this->ltiInstanceCollection = new UniqueLtiInstanceCollection();

        $this->subject = new GroupIdLoadBalancerFactory(
            $this->ltiInstanceRepository,
        );
    }

    public function testItThrowsExceptionIfLtiInstanceNotExist(): void
    {
        $this->expectException(LtiInstanceNotFoundException::class);
        $this->expectExceptionMessage("No Lti instance were found in database.");

        $this->subject->getLoadBalanceGroupID('testGroup');
    }

    public function testItCanCreateGroupIds(): void
    {
        $this->ltiInstanceCollection
            ->add(new LtiInstance(1, 'infra_1', 'http://lb_infra_1', 'key', 'secret'))
            ->add(new LtiInstance(2, 'infra_2', 'http://lb_infra_2', 'key', 'secret'));
        $this->ltiInstanceRepository
            ->expects(self::once())
            ->method('findAllAsCollection')
            ->willReturn($this->ltiInstanceCollection);

        $output = $this->subject->getLoadBalanceGroupID('testGroup');
        self::assertIsArray($output);
    }
}
