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

use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Lti\Exception\RegistrationNotFoundException;
use OAT\SimpleRoster\Lti\Request\LtiRequest;

class Lti1p3RequestFactory implements LtiRequestFactoryInterface
{
    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $ltiRequestBuilder;

    /** @var string */
    private $ltiRegistrationId;

    /** @var string */
    private $ltiLaunchPresentationReturnUrl;

    /** @var string */
    private $ltiLaunchPresentationLocale;

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        LtiResourceLinkLaunchRequestBuilder $ltiRequestBuilder,
        string $ltiRegistrationId,
        string $ltiLaunchPresentationReturnUrl,
        string $ltiLaunchPresentationLocale
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->ltiRequestBuilder = $ltiRequestBuilder;
        $this->ltiRegistrationId = $ltiRegistrationId;
        $this->ltiLaunchPresentationReturnUrl = $ltiLaunchPresentationReturnUrl;
        $this->ltiLaunchPresentationLocale = $ltiLaunchPresentationLocale;
    }

    public function create(Assignment $assignment): LtiRequest
    {
        $lineItem = $assignment->getLineItem();
        $resourceLink = new LtiResourceLink($lineItem->getUri());
        $registration = $this->registrationRepository->find($this->ltiRegistrationId);

        if (!$registration) {
            throw new RegistrationNotFoundException(sprintf('Registration %s not found.', $this->ltiRegistrationId));
        }

        $loginHint = new LoginHintDto(
            (string)$assignment->getUser()->getUsername(),
            (int)$assignment->getId(),
        );

        $message = $this->ltiRequestBuilder->buildLtiResourceLinkLaunchRequest(
            $resourceLink,
            $registration,
            (string) $loginHint,
            null,
            [
                LtiRequest::LTI_ROLE
            ],
            [
                new LaunchPresentationClaim(
                    null,
                    null,
                    null,
                    $this->ltiLaunchPresentationReturnUrl,
                    $this->ltiLaunchPresentationLocale,
                ),
                new ContextClaim(
                    (string)$assignment->getId(),
                    [],
                    $lineItem->getSlug(),
                    $lineItem->getLabel(),
                )
            ]
        );

        return new LtiRequest($message->toUrl(), LtiRequest::LTI_VERSION_1P3, []);
    }
}
