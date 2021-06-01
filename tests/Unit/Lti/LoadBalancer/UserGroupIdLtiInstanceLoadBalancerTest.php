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
use OAT\SimpleRoster\Lti\Exception\IndeterminableLtiInstanceUrlException;
use OAT\SimpleRoster\Lti\Exception\IndeterminableLtiRequestContextIdException;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\LoadBalancer\UserGroupIdLtiInstanceLoadBalancer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class UserGroupIdLtiInstanceLoadBalancerTest extends TestCase
{
    private UniqueLtiInstanceCollection $ltiInstanceCollection;
    private UserGroupIdLtiInstanceLoadBalancer $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiInstanceCollection = new UniqueLtiInstanceCollection();
        $this->ltiInstanceCollection
            ->add(new LtiInstance(new UuidV6(), 'label1', 'link1', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6(), 'label2', 'link2', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6(), 'label3', 'link3', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6(), 'label4', 'link4', 'key', 'secret'))
            ->add(new LtiInstance(new UuidV6(), 'label5', 'link5', 'key', 'secret'));

        $this->subject = new UserGroupIdLtiInstanceLoadBalancer($this->ltiInstanceCollection);
    }

    public function testIfItIsLtiInstanceLoadBalancer(): void
    {
        self::assertInstanceOf(LtiInstanceLoadBalancerInterface::class, $this->subject);
    }

    public function testItThrowsExceptionIfLtiInstanceUrlCannotBeDeterminedDueToMissingGroupId(): void
    {
        $this->expectException(IndeterminableLtiInstanceUrlException::class);

        $this->subject->getLtiInstance(new User(new UuidV6(), 'testUser', 'testPassword'));
    }

    public function testItCanLoadBalanceByUsername(): void
    {
        $expectedResultsMap = [
            'userGroupId_1' => $this->ltiInstanceCollection->getByIndex(3),
            'userGroupId_2' => $this->ltiInstanceCollection->getByIndex(3),
            'userGroupId_3' => $this->ltiInstanceCollection->getByIndex(2),
            'userGroupId_4' => $this->ltiInstanceCollection->getByIndex(4),
            'userGroupId_5' => $this->ltiInstanceCollection->getByIndex(1),
            'userGroupId_6' => $this->ltiInstanceCollection->getByIndex(1),
            'userGroupId_7' => $this->ltiInstanceCollection->getByIndex(0),
            'userGroupId_8' => $this->ltiInstanceCollection->getByIndex(3),
            'userGroupId_9' => $this->ltiInstanceCollection->getByIndex(1),
            'userGroupId_10' => $this->ltiInstanceCollection->getByIndex(0),
        ];

        /** @var LtiInstance $expectedLtiInstance */
        foreach ($expectedResultsMap as $userGroupId => $expectedLtiInstance) {
            $user = new User(new UuidV6(), 'testUser', 'testPassword', $userGroupId);

            $actualLtiInstance = $this->subject->getLtiInstance($user);

            self::assertSame(
                $expectedLtiInstance,
                $actualLtiInstance,
                sprintf(
                    "Expected LTI instance url for user with username '%s' is '%s', '%s' received",
                    $userGroupId,
                    $expectedLtiInstance->getLabel(),
                    $actualLtiInstance->getLabel()
                )
            );
        }
    }

    public function testItThrowsExceptionIfLtiRequestContextIdCannotBeDetermined(): void
    {
        $this->expectException(IndeterminableLtiRequestContextIdException::class);

        $user = new User(new UuidV6(), 'testUser', 'testPassword');
        $lineItem = new LineItem(new UuidV6(), 'label', 'uri', 'slug', LineItem::STATUS_ENABLED);
        $assignment = new Assignment(new UuidV6(), Assignment::STATUS_READY, $lineItem);

        $user->addAssignment($assignment);

        $this->subject->getLtiRequestContextId($assignment);
    }

    public function testItCanReturnLtiRequestContextId(): void
    {
        $user = new User(new UuidV6(), 'testUser', 'testPassword', 'group_5');
        $lineItem = new LineItem(new UuidV6(), 'label', 'uri', 'slug', LineItem::STATUS_ENABLED);
        $assignment = new Assignment(new UuidV6(), Assignment::STATUS_READY, $lineItem);

        $user->addAssignment($assignment);

        self::assertSame('group_5', $this->subject->getLtiRequestContextId($assignment));
    }
}
