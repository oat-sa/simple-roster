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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Lti\LoadBalancer;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Exception\IndeterminableLtiInstanceUrlException;
use OAT\SimpleRoster\Lti\Exception\IndeterminableLtiRequestContextIdException;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\LoadBalancer\UserGroupIdLtiInstanceLoadBalancer;
use PHPUnit\Framework\TestCase;

class UserGroupIdLtiInstanceLoadBalancerTest extends TestCase
{
    /** @var UserGroupIdLtiInstanceLoadBalancer */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new UserGroupIdLtiInstanceLoadBalancer([
            'http://lb_infra_1',
            'http://lb_infra_2',
            'http://lb_infra_3',
            'http://lb_infra_4',
            'http://lb_infra_5',
        ]);
    }

    public function testIfItIsLtiInstanceLoadBalancer(): void
    {
        self::assertInstanceOf(LtiInstanceLoadBalancerInterface::class, $this->subject);
    }

    public function testItThrowsExceptionIfLtiInstanceUrlCannotBeDetermined(): void
    {
        $this->expectException(IndeterminableLtiInstanceUrlException::class);

        $this->subject->getLtiInstanceUrl(new User());
    }

    public function testItCanLoadBalanceByUsername(): void
    {
        $expectedResultsMap = [
            'userGroupId_1' => 'http://lb_infra_4',
            'userGroupId_2' => 'http://lb_infra_4',
            'userGroupId_3' => 'http://lb_infra_3',
            'userGroupId_4' => 'http://lb_infra_5',
            'userGroupId_5' => 'http://lb_infra_2',
            'userGroupId_6' => 'http://lb_infra_2',
            'userGroupId_7' => 'http://lb_infra_1',
            'userGroupId_8' => 'http://lb_infra_4',
            'userGroupId_9' => 'http://lb_infra_2',
            'userGroupId_10' => 'http://lb_infra_1',
        ];

        foreach ($expectedResultsMap as $userGroupId => $expectedLtiInstanceUrl) {
            $user = (new User())->setGroupId($userGroupId);

            $actualLtiInstanceUrl = $this->subject->getLtiInstanceUrl($user);

            self::assertSame(
                $expectedLtiInstanceUrl,
                $actualLtiInstanceUrl,
                sprintf(
                    "Expected LTI instance url for user with username '%s' is '%s', '%s' received",
                    $userGroupId,
                    $expectedLtiInstanceUrl,
                    $actualLtiInstanceUrl
                )
            );
        }
    }

    public function testItThrowsExceptionIfLtiRequestContextIdCannotBeDetermined(): void
    {
        $this->expectException(IndeterminableLtiRequestContextIdException::class);

        $assignment = (new Assignment())->setUser(new User());

        $this->subject->getLtiRequestContextId($assignment);
    }

    public function testItCanReturnLtiRequestContextId(): void
    {
        $user = (new User())->setGroupId('group_5');
        $assignment = (new Assignment())->setUser($user);

        $this->assertSame('group_5', $this->subject->getLtiRequestContextId($assignment));
    }
}
