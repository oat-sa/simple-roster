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

use App\Lti\LoadBalancer\LtiInstanceLoadBalancer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class LtiInstanceLoadBalancerTest extends TestCase
{
    /** @var LtiInstanceLoadBalancer */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new LtiInstanceLoadBalancer([
            'http://lb_infra_1',
            'http://lb_infra_2',
            'http://lb_infra_3',
            'http://lb_infra_4',
            'http://lb_infra_5',
            'http://lb_infra_6',
            'http://lb_infra_7',
            'http://lb_infra_8',
            'http://lb_infra_9',
            'http://lb_infra_10',
        ]);
    }

    public function testItReturnsAlwaysSameLoadBalancedInstanceUrlWithSameInputs(): void
    {
        $output1 = $this->subject->getLoadBalancedLtiInstanceUrl('user');
        $output2 = $this->subject->getLoadBalancedLtiInstanceUrl('user');
        $output3 = $this->subject->getLoadBalancedLtiInstanceUrl('user');
        $output4 = $this->subject->getLoadBalancedLtiInstanceUrl('user');

        $this->assertEquals($output1, $output2);
        $this->assertEquals($output1, $output3);
        $this->assertEquals($output1, $output4);
    }

    public function testItReturnsDifferentLoadBalancedInstanceUrlWithDifferentInputs(): void
    {
        $output1 = $this->subject->getLoadBalancedLtiInstanceUrl('user1');
        $output2 = $this->subject->getLoadBalancedLtiInstanceUrl('user2');
        $output3 = $this->subject->getLoadBalancedLtiInstanceUrl('user3');
        $output4 = $this->subject->getLoadBalancedLtiInstanceUrl('user4');

        $this->assertNotEquals($output1, $output2);
        $this->assertNotEquals($output1, $output3);
        $this->assertNotEquals($output1, $output4);
        $this->assertNotEquals($output2, $output3);
        $this->assertNotEquals($output2, $output4);
        $this->assertNotEquals($output3, $output4);
    }
}
