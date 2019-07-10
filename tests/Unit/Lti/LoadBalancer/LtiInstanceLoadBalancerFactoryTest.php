<?php declare(strict_types=1);

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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Tests\Unit\Lti\LoadBalancer;

use App\Lti\LoadBalancer\LtiInstanceLoadBalancerFactory;
use App\Lti\LoadBalancer\UserGroupIdLtiInstanceLoadBalancer;
use App\Lti\LoadBalancer\UsernameLtiInstanceLoadBalancer;
use PHPUnit\Framework\TestCase;

class LtiInstanceLoadBalancerFactoryTest extends TestCase
{
    /** @var LtiInstanceLoadBalancerFactory */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new LtiInstanceLoadBalancerFactory([]);
    }

    public function testItCanResolveUsernameLtiInstanceLoadBalancerStrategy(): void
    {
        $this->assertInstanceOf(
            UsernameLtiInstanceLoadBalancer::class,
            call_user_func($this->subject, LtiInstanceLoadBalancerFactory::LOAD_BALANCER_STRATEGY_USERNAME)
        );
    }

    public function testItCanResolveUserGroupIdLtiInstanceLoadBalancerStrategy(): void
    {
        $this->assertInstanceOf(
            UserGroupIdLtiInstanceLoadBalancer::class,
            call_user_func($this->subject, LtiInstanceLoadBalancerFactory::LOAD_BALANCER_STRATEGY_USER_GROUP_ID)
        );
    }

    public function testItThrowsExceptionIfInvalidLoadBalancerStrategyReceived(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Invalid load balancer strategy received. Possible values: username, userGroupId'
        );

        call_user_func($this->subject, 'invalid');
    }
}
