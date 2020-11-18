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

use LogicException;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Generator\NonceGenerator;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\LoadBalancer\LtiInstanceLoadBalancerInterface;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use OAT\SimpleRoster\Security\OAuth\OAuthSigner;
use Symfony\Component\Routing\RouterInterface;

class LtiRequestFactory
{
    /** @var LtiConfiguration */
    private $ltiConfiguration;

    /** @var OAuthSigner */
    private $signer;

    /** @var NonceGenerator */
    private $generator;

    /** @var RouterInterface */
    private $router;

    /** @var LtiInstanceLoadBalancerInterface */
    private $loadBalancer;

    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $ltiRequestBuilder;

    public function __construct(
        LtiConfiguration $ltiConfiguration,
        OAuthSigner $signer,
        NonceGenerator $generator,
        RouterInterface $router,
        LtiInstanceLoadBalancerInterface $loadBalancer,
        RegistrationRepositoryInterface $registrationRepository,
        LtiResourceLinkLaunchRequestBuilder $ltiRequestBuilder
    ) {
        $this->ltiConfiguration = $ltiConfiguration;
        $this->signer = $signer;
        $this->generator = $generator;
        $this->router = $router;
        $this->loadBalancer = $loadBalancer;
        $this->registrationRepository = $registrationRepository;
        $this->ltiRequestBuilder = $ltiRequestBuilder;
    }

    /**
     * @throws LogicException
     */
    public function __invoke(): LtiRequestFactoryInterface
    {
        switch ($this->ltiConfiguration->getLtiVersion()) {
            case LtiRequest::LTI_VERSION_1P1:
                return new Lti1p1RequestFactory(
                    $this->signer,
                    $this->generator,
                    $this->router,
                    $this->loadBalancer,
                    $this->ltiConfiguration
                );
            case LtiRequest::LTI_VERSION_1P3:
                return new Lti1p3RequestFactory(
                    $this->registrationRepository,
                    $this->ltiRequestBuilder,
                    $this->ltiConfiguration
                );
            default:
                throw new LogicException(
                    'Invalid LTI Version specified: ' . $this->ltiConfiguration->getLtiVersion()
                );
        }
    }
}
