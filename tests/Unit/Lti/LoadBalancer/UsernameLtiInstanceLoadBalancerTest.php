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
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Collection\UniqueLtiInstanceCollection;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\LoadBalancer\UsernameLtiInstanceLoadBalancer;
use PHPUnit\Framework\TestCase;

class UsernameLtiInstanceLoadBalancerTest extends TestCase
{
    private UniqueLtiInstanceCollection $ltiInstanceCollection;
    private UsernameLtiInstanceLoadBalancer $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiInstanceCollection = new UniqueLtiInstanceCollection();
        $this->ltiInstanceCollection
            ->add(new LtiInstance(1, 'infra_1', 'http://lb_infra_1', 'key', 'secret'))
            ->add(new LtiInstance(2, 'infra_2', 'http://lb_infra_2', 'key', 'secret'))
            ->add(new LtiInstance(3, 'infra_3', 'http://lb_infra_3', 'key', 'secret'))
            ->add(new LtiInstance(4, 'infra_4', 'http://lb_infra_4', 'key', 'secret'))
            ->add(new LtiInstance(5, 'infra_5', 'http://lb_infra_5', 'key', 'secret'));

        $this->subject = new UsernameLtiInstanceLoadBalancer($this->ltiInstanceCollection);
    }

    public function testIfItIsLtiInstanceLoadBalancer(): void
    {
        self::assertInstanceOf(LtiInstanceLoadBalancerInterface::class, $this->subject);
    }

    public function testItCanLoadBalanceByUsername(): void
    {
        $expectedLtiInstanceMap = [
            'user1' => $this->ltiInstanceCollection->getByIndex(2),
            'user2' => $this->ltiInstanceCollection->getByIndex(4),
            'user3' => $this->ltiInstanceCollection->getByIndex(3),
            'user4' => $this->ltiInstanceCollection->getByIndex(1),
            'user5' => $this->ltiInstanceCollection->getByIndex(4),
            'user6' => $this->ltiInstanceCollection->getByIndex(1),
            'user7' => $this->ltiInstanceCollection->getByIndex(2),
            'user8' => $this->ltiInstanceCollection->getByIndex(3),
            'user9' => $this->ltiInstanceCollection->getByIndex(2),
            'user10' => $this->ltiInstanceCollection->getByIndex(0),
        ];

        /** @var LtiInstance $expectedLtiInstance */
        foreach ($expectedLtiInstanceMap as $username => $expectedLtiInstance) {
            $user = (new User())->setUsername($username);

            $actualLtiInstance = $this->subject->getLtiInstance($user);

            self::assertSame(
                $expectedLtiInstance,
                $actualLtiInstance,
                sprintf(
                    "Expected LTI instance url for user with username '%s' is '%s', '%s' received",
                    $username,
                    $expectedLtiInstance->getLabel(),
                    $actualLtiInstance->getLabel()
                )
            );
        }
    }

    public function testItCanReturnLtiRequestContextId(): void
    {
        $assignment = (new Assignment())->setLineItem($this->getLineItemMock(5));

        self::assertSame('5', $this->subject->getLtiRequestContextId($assignment));
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
