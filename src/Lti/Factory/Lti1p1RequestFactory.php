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

namespace OAT\SimpleRoster\Lti\Factory;

use Carbon\Carbon;
use JsonException;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\Builder\Lti1p1LaunchUrlBuilder;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use Symfony\Component\HttpFoundation\Request;

class Lti1p1RequestFactory implements LtiRequestFactoryInterface
{
    private Lti1p1LaunchUrlBuilder $launchUrlBuilder;
    private OAuthSigner $signer;
    private NonceGenerator $generator;
    private LtiInstanceLoadBalancerInterface $loadBalancer;

    public function __construct(
        Lti1p1LaunchUrlBuilder $launchUrlBuilder,
        OAuthSigner $signer,
        NonceGenerator $generator,
        LtiInstanceLoadBalancerInterface $loadBalancer
    ) {
        $this->launchUrlBuilder = $launchUrlBuilder;
        $this->signer = $signer;
        $this->generator = $generator;
        $this->loadBalancer = $loadBalancer;
    }

    /**
     * @throws JsonException
     */
    public function create(Assignment $assignment): LtiRequest
    {
        $ltiInstance = $this->loadBalancer->getLtiInstance($assignment->getUser());

        $context = new OAuthContext(
            '',
            $ltiInstance->getLtiKey(),
            $this->generator->generate(),
            OAuthContext::METHOD_MAC_SHA1,
            (string)Carbon::now()->getTimestamp(),
            OAuthContext::VERSION_1_0
        );

        $ltiLaunchUrl = $this->launchUrlBuilder->build($ltiInstance, $assignment);
        $ltiLink = $ltiLaunchUrl->getLtiLink();
        $ltiParameters = $ltiLaunchUrl->getLtiParameters();

        $signature = $this->signer->sign(
            $context,
            $ltiLink,
            Request::METHOD_POST,
            $ltiInstance->getLtiSecret(),
            $ltiParameters
        );

        return new LtiRequest(
            $ltiLink,
            LtiRequest::LTI_VERSION_1P1,
            array_merge(
                [
                    'oauth_body_hash' => $context->getBodyHash(),
                    'oauth_consumer_key' => $context->getConsumerKey(),
                    'oauth_nonce' => $context->getNonce(),
                    'oauth_signature' => $signature,
                    'oauth_signature_method' => $context->getSignatureMethod(),
                    'oauth_timestamp' => $context->getTimestamp(),
                    'oauth_version' => $context->getVersion(),
                ],
                $ltiParameters
            )
        );
    }
}
