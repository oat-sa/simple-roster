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

namespace App\Tests\Unit\Lti\LoadBalancer;

use App\Entity\Assignment;
use App\Entity\LineItem;
use App\Entity\User;
use App\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use App\Lti\LoadBalancer\UsernameLtiInstanceLoadBalancer;
use PHPUnit\Framework\TestCase;

class UsernameLtiInstanceLoadBalancerTest extends TestCase
{
    /** @var UsernameLtiInstanceLoadBalancer */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new UsernameLtiInstanceLoadBalancer([
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

    public function testItCanLoadBalanceByUsername(): void
    {
        $expectedResultsMap = [
            'user1' => 'http://lb_infra_3',
            'user2' => 'http://lb_infra_5',
            'user3' => 'http://lb_infra_4',
            'user4' => 'http://lb_infra_2',
            'user5' => 'http://lb_infra_5',
            'user6' => 'http://lb_infra_2',
            'user7' => 'http://lb_infra_3',
            'user8' => 'http://lb_infra_4',
            'user9' => 'http://lb_infra_3',
            'user10' => 'http://lb_infra_1',
        ];

        foreach ($expectedResultsMap as $username => $expectedLtiInstanceUrl) {
            $user = (new User())->setUsername($username);

            $actualLtiInstanceUrl = $this->subject->getLtiInstanceUrl($user);

            self::assertSame(
                $expectedLtiInstanceUrl,
                $actualLtiInstanceUrl,
                sprintf(
                    "Expected LTI instance url for user with username '%s' is '%s', '%s' received",
                    $username,
                    $expectedLtiInstanceUrl,
                    $actualLtiInstanceUrl
                )
            );
        }
    }

    public function testItCanReturnLtiRequestContextId(): void
    {
        $assignment = (new Assignment())->setLineItem($this->getLineItemMock(5));

        $this->assertSame('5', $this->subject->getLtiRequestContextId($assignment));
    }

    private function getLineItemMock(int $lineItemId): LineItem
    {
        $lineItem = $this->createMock(LineItem::class);
        $lineItem
            ->method('getId')
            ->willReturn($lineItemId);

        return $lineItem;
    }
}
