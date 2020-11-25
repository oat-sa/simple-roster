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

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Exception\RegistrationNotFoundException;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Lti1p3RequestFactory implements LtiRequestFactoryInterface
{
    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $ltiRequestBuilder;

    /** @var RouterInterface */
    private $router;

    /** @var LtiConfiguration */
    private $ltiConfiguration;

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        LtiResourceLinkLaunchRequestBuilder $ltiRequestBuilder,
        RouterInterface $router,
        LtiConfiguration $ltiConfiguration
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->ltiConfiguration = $ltiConfiguration;
        $this->ltiRequestBuilder = $ltiRequestBuilder;
        $this->router = $router;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function create(Assignment $assignment): LtiRequest
    {
        $ltiRegistrationId = $this->ltiConfiguration->getLtiRegistrationId();
        $registration = $this->registrationRepository->find($ltiRegistrationId);

        if (!$registration) {
            throw new RegistrationNotFoundException(sprintf('Registration %s not found.', $ltiRegistrationId));
        }

        $loginHint = new LoginHintDto(
            (string)$assignment->getUser()->getUsername(),
            (int)$assignment->getId(),
        );

        $message = $this->buildLtiMessage($registration, $loginHint, $assignment);

        return new LtiRequest($message->toUrl(), LtiRequest::LTI_VERSION_1P3, []);
    }

    /**
     * @throws LtiExceptionInterface
     */
    private function buildLtiMessage(
        RegistrationInterface $registration,
        LoginHintDto $loginHint,
        Assignment $assignment
    ): LtiMessageInterface {
        $lineItem = $assignment->getLineItem();
        $resourceLink = new LtiResourceLink($lineItem->getUri());

        return $this->ltiRequestBuilder->buildLtiResourceLinkLaunchRequest(
            $resourceLink,
            $registration,
            (string)$loginHint,
            null,
            [
                LtiRequest::LTI_ROLE,
            ],
            [
                new BasicOutcomeClaim(
                    (string) $assignment->getId(),
                    $this->router->generate(
                        'updateLtiOutcome',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ),
                new LaunchPresentationClaim(
                    null,
                    null,
                    null,
                    $this->ltiConfiguration->getLtiLaunchPresentationReturnUrl(),
                    $this->ltiConfiguration->getLtiLaunchPresentationLocale(),
                ),
                new ContextClaim(
                    (string)$assignment->getId(),
                    [],
                    $lineItem->getSlug(),
                    $lineItem->getLabel(),
                ),
            ]
        );
    }
}
