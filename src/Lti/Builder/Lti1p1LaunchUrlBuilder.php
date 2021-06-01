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

namespace OAT\SimpleRoster\Lti\Builder;

use JsonException;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Model\Lti1p1LaunchUrl;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Lti1p1LaunchUrlBuilder
{
    private const LTI_VERSION_REQUEST_STRING = 'LTI-1p0';

    private RouterInterface $router;
    private LtiInstanceLoadBalancerInterface $loadBalancer;
    private LtiConfiguration $ltiConfiguration;

    public function __construct(
        RouterInterface $router,
        LtiInstanceLoadBalancerInterface $loadBalancer,
        LtiConfiguration $ltiConfiguration
    ) {
        $this->router = $router;
        $this->loadBalancer = $loadBalancer;
        $this->ltiConfiguration = $ltiConfiguration;
    }

    /**
     * @throws JsonException
     */
    public function build(LtiInstance $ltiInstance, Assignment $assignment): Lti1p1LaunchUrl
    {
        return new Lti1p1LaunchUrl(
            $this->getLtiLaunchLink($ltiInstance, $assignment->getLineItem()),
            $this->getAssignmentLtiParameters($assignment)
        );
    }

    /**
     * @throws JsonException
     */
    private function getLtiLaunchLink(LtiInstance $ltiInstance, LineItem $lineItem): string
    {
        return sprintf(
            '%s/ltiDeliveryProvider/DeliveryTool/launch/%s',
            rtrim($ltiInstance->getLtiLink(), '/'),
            base64_encode(
                json_encode(
                    ['delivery' => $lineItem->getUri()],
                    JSON_THROW_ON_ERROR
                )
            )
        );
    }

    private function getAssignmentLtiParameters(Assignment $assignment): array
    {
        return [
            'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
            'lti_version' => self::LTI_VERSION_REQUEST_STRING,
            'context_id' => $this->loadBalancer->getLtiRequestContextId($assignment),
            'roles' => LtiRequest::LTI_ROLE,
            'user_id' => $assignment->getUser()->getUsername(),
            'lis_person_name_full' => $assignment->getUser()->getUsername(),
            'resource_link_id' => (string)$assignment->getId(),
            'lis_outcome_service_url' => $this->router->generate(
                'updateLti1p1Outcome',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'lis_result_sourcedid' => (string)$assignment->getId(),
            'launch_presentation_return_url' => $this->ltiConfiguration->getLtiLaunchPresentationReturnUrl(),
            'launch_presentation_locale' => $this->ltiConfiguration->getLtiLaunchPresentationLocale(),
        ];
    }
}
