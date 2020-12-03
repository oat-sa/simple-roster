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

use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Request\LtiRequest;

class Lti1p3MessageBuilder
{
    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $ltiRequestBuilder;

    /** @var LtiConfiguration */
    private $ltiConfiguration;

    public function __construct(
        LtiResourceLinkLaunchRequestBuilder $ltiRequestBuilder,
        LtiConfiguration $ltiConfiguration
    ) {
        $this->ltiRequestBuilder = $ltiRequestBuilder;
        $this->ltiConfiguration = $ltiConfiguration;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function build(
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
