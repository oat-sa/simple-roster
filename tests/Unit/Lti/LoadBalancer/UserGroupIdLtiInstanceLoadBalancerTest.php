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

use App\Entity\LtiInstance;
use App\Entity\Assignment;
use App\Entity\User;
use App\Lti\Collection\LtiInstanceCollection;
use App\Lti\Exception\IndeterminableLtiInstanceUrlException;
use App\Lti\Exception\IndeterminableLtiRequestContextIdException;
use App\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use App\Lti\LoadBalancer\UserGroupIdLtiInstanceLoadBalancer;
use PHPUnit\Framework\TestCase;

class UserGroupIdLtiInstanceLoadBalancerTest extends TestCase
{
    /** @var LtiInstanceCollection */
    private $ltiInstanceCollection;

    /** @var UserGroupIdLtiInstanceLoadBalancer */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ltiInstanceCollection = new LtiInstanceCollection();
        $this->ltiInstanceCollection
            ->add(new LtiInstance(1, 'infra_1', 'http://lb_infra_1', 'key', 'secret'))
            ->add(new LtiInstance(2, 'infra_2', 'http://lb_infra_2', 'key', 'secret'))
            ->add(new LtiInstance(3, 'infra_3', 'http://lb_infra_3', 'key', 'secret'))
            ->add(new LtiInstance(4, 'infra_4', 'http://lb_infra_4', 'key', 'secret'))
            ->add(new LtiInstance(5, 'infra_5', 'http://lb_infra_5', 'key', 'secret'));

        $this->subject = new UserGroupIdLtiInstanceLoadBalancer($this->ltiInstanceCollection);
    }

    public function testIfItIsLtiInstanceLoadBalancer(): void
    {
        self::assertInstanceOf(LtiInstanceLoadBalancerInterface::class, $this->subject);
    }

    public function testItThrowsExceptionIfLtiInstanceUrlCannotBeDetermined(): void
    {
        $this->expectException(IndeterminableLtiInstanceUrlException::class);

        $this->subject->getLtiInstance(new User());
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
            $user = (new User())->setGroupId($userGroupId);

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
