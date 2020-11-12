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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\Factory;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use Carbon\Carbon;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Lti1p1RequestFactory implements LtiRequestFactoryInterface
{
    /** @var OAuthSigner */
    private $signer;

    /** @var NonceGenerator */
    private $generator;

    /** @var RouterInterface */
    private $router;

    /** @var LtiInstanceLoadBalancerInterface */
    private $loadBalancer;

    /** @var string */
    private $ltiLaunchPresentationReturnUrl;

    /** @var string */
    private $ltiLaunchPresentationLocale;

    /** @var bool */
    private $ltiInstancesLoadBalancerEnabled;

    /** @var string */
    private $ltiKey;

    /** @var string */
    private $ltiSecret;

    public function __construct(
        OAuthSigner $signer,
        NonceGenerator $generator,
        RouterInterface $router,
        LtiInstanceLoadBalancerInterface $loadBalancer,
        string $ltiLaunchPresentationReturnUrl,
        string $ltiLaunchPresentationLocale,
        bool $ltiInstancesLoadBalancerEnabled,
        string $ltiKey,
        string $ltiSecret
    ) {
        $this->signer = $signer;
        $this->generator = $generator;
        $this->router = $router;
        $this->loadBalancer = $loadBalancer;
        $this->ltiLaunchPresentationReturnUrl = $ltiLaunchPresentationReturnUrl;
        $this->ltiLaunchPresentationLocale = $ltiLaunchPresentationLocale;
        $this->ltiInstancesLoadBalancerEnabled = $ltiInstancesLoadBalancerEnabled;
        $this->ltiKey = $ltiKey;
        $this->ltiSecret = $ltiSecret;
    }

    public function create(Assignment $assignment): LtiRequest
    {
        $ltiLink = $this->getAssignmentLtiLink($assignment);
        $ltiParameters = $this->getAssignmentLtiParameters($assignment);
        $contextParameters = $this->getContextParameters($ltiLink, $ltiParameters);

        return new LtiRequest(
            $ltiLink,
            LtiRequest::LTI_VERSION_1P1,
            array_merge($contextParameters, $ltiParameters)
        );
    }

    private function getAssignmentLtiLink(Assignment $assignment): string
    {
        $link = $this->ltiInstancesLoadBalancerEnabled
            ? $this->loadBalancer->getLtiInstanceUrl($assignment->getUser())
            : $assignment->getLineItem()->getInfrastructure()->getLtiDirectorLink();

        return sprintf(
            '%s/%s',
            $link,
            base64_encode(
                json_encode(
                    ['delivery' => $assignment->getLineItem()->getUri()],
                    JSON_THROW_ON_ERROR,
                    512
                )
            )
        );
    }

    private function getContextParameters(string $ltiLink, array $ltiParameters): array
    {
        $context = new OAuthContext(
            '',
            $this->ltiKey,
            $this->generator->generate(),
            OAuthContext::METHOD_MAC_SHA1,
            (string)Carbon::now()->getTimestamp(),
            OAuthContext::VERSION_1_0
        );

        $signature = $this->signer->sign(
            $context,
            $ltiLink,
            Request::METHOD_POST,
            $this->ltiSecret,
            $ltiParameters
        );

        return [
            'oauth_body_hash' => $context->getBodyHash(),
            'oauth_consumer_key' => $context->getConsumerKey(),
            'oauth_nonce' => $context->getNonce(),
            'oauth_signature' => $signature,
            'oauth_signature_method' => $context->getSignatureMethod(),
            'oauth_timestamp' => $context->getTimestamp(),
            'oauth_version' => $context->getVersion(),
        ];
    }

    private function getAssignmentLtiParameters(Assignment $assignment): array
    {
        return [
            'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
            'context_id' => $this->loadBalancer->getLtiRequestContextId($assignment),
            'roles' => LtiRequest::LTI_ROLE,
            'user_id' => $assignment->getUser()->getUsername(),
            'lis_person_name_full' => $assignment->getUser()->getUsername(),
            'resource_link_id' => $assignment->getId(),
            'lis_outcome_service_url' => $this->router->generate(
                'updateLtiOutcome',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'lis_result_sourcedid' => $assignment->getId(),
            'launch_presentation_return_url' => $this->ltiLaunchPresentationReturnUrl,
            'launch_presentation_locale' => $this->ltiLaunchPresentationLocale,
        ];
    }
}
