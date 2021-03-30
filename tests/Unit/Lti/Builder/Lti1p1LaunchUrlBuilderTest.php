<?php

/*
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

namespace OAT\SimpleRoster\Tests\Unit\Lti\Builder;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Lti\Builder\Lti1p1LaunchUrlBuilder;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\UuidV6;

class Lti1p1LaunchUrlBuilderTest extends TestCase
{
    public function testItCanBuildLtiLaunchUrl(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('http://test-service-url');

        $loadBalancer = $this->createMock(LtiInstanceLoadBalancerInterface::class);
        $loadBalancer
            ->method('getLtiRequestContextId')
            ->willReturn('contextId');

        $ltiConfiguration = new LtiConfiguration(LtiRequest::LTI_VERSION_1P1, 'returnUrl', 'en-EN', 'registrationId');

        $ltiInstanceId = new UuidV6('00000001-0000-6000-0000-000000000000');
        $ltiInstance = new LtiInstance($ltiInstanceId, 'ltiInstance', 'ltiLink', 'ltiKey', 'ltiSecret');

        $lineItem = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'testLabel',
            'http://test-uri.com',
            'testSlug',
            LineItem::STATUS_ENABLED
        );

        $assignment = (new Assignment())
            ->setId(new UuidV6('00000010-0000-6000-0000-000000000000'))
            ->setUser((new User())->setUsername('testUser'))
            ->setLineItem($lineItem);

        $subject = new Lti1p1LaunchUrlBuilder($router, $loadBalancer, $ltiConfiguration);

        $launchUrl = $subject->build($ltiInstance, $assignment);

        self::assertSame(
            'ltiLink/ltiDeliveryProvider/DeliveryTool/launch/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL3Rlc3QtdXJpLmNvbSJ9',
            $launchUrl->getLtiLink()
        );

        self::assertSame(
            [
                'lti_message_type' => 'basic-lti-launch-request',
                'lti_version' => 'LTI-1p0',
                'context_id' => 'contextId',
                'roles' => 'Learner',
                'user_id' => 'testUser',
                'lis_person_name_full' => 'testUser',
                'resource_link_id' => '00000010-0000-6000-0000-000000000000',
                'lis_outcome_service_url' => 'http://test-service-url',
                'lis_result_sourcedid' => '00000010-0000-6000-0000-000000000000',
                'launch_presentation_return_url' => 'returnUrl',
                'launch_presentation_locale' => 'en-EN',
            ],
            $launchUrl->getLtiParameters()
        );
    }
}
