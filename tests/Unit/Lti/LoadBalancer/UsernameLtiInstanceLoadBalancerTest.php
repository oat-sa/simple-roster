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
use Symfony\Component\Uid\UuidV6;

class UsernameLtiInstanceLoadBalancerTest extends TestCase
{
    /** @var UniqueLtiInstanceCollection */
    private $ltiInstanceCollection;

    /** @var UsernameLtiInstanceLoadBalancer */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiInstanceCollection = new UniqueLtiInstanceCollection();
        $this->ltiInstanceCollection
            ->add(new LtiInstance(new UuidV6('00000001-0000-6000-0000-000000000000'), '1', 'link1', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6('00000002-0000-6000-0000-000000000000'), '2', 'link2', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6('00000003-0000-6000-0000-000000000000'), '3', 'link3', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6('00000004-0000-6000-0000-000000000000'), '4', 'link4', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6('00000005-0000-6000-0000-000000000000'), '5', 'link5', 'key', 'secret'));

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
        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED
        );

        $assignment = new Assignment(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            Assignment::STATUS_READY,
            $lineItem
        );

        self::assertSame('00000001-0000-6000-0000-000000000000', $this->subject->getLtiRequestContextId($assignment));
    }
}
