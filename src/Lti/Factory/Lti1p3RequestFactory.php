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
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\SimpleRoster\DataTransferObject\LoginHintDto;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Lti\Exception\RegistrationNotFoundException;
use OAT\SimpleRoster\Lti\Request\LtiRequest;

class Lti1p3RequestFactory implements LtiRequestFactoryInterface
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    /** @var string */
    private $ltiRegistrationId;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        LtiResourceLinkLaunchRequestBuilder $builder,
        string $ltiRegistrationId
    ) {
        $this->repository = $repository;
        $this->builder = $builder;
        $this->ltiRegistrationId = $ltiRegistrationId;
    }

    public function create(Assignment $assignment): LtiRequest
    {
        $resourceLink = new LtiResourceLink($assignment->getLineItem()->getUri());
        $registration = $this->repository->find($this->ltiRegistrationId);

        if (!$registration) {
            throw new RegistrationNotFoundException(sprintf('Registration %s not found.', $this->ltiRegistrationId));
        }

        $loginHint = new LoginHintDto(
            (string) $assignment->getUser()->getUsername(),
            (string) $assignment->getUser()->getGroupId(),
            $assignment->getLineItem()->getSlug()
        );

        $message = $this->builder->buildLtiResourceLinkLaunchRequest(
            $resourceLink,
            $registration,
            (string) $loginHint,
            null,
            [
                LtiRequest::LTI_ROLE
            ],
            [
                new ContextClaim(
                    (string)$assignment->getId(),
                    [],
                    $assignment->getLineItem()->getSlug(),
                    $assignment->getLineItem()->getLabel(),
                )
            ]
        );

        return new LtiRequest($message->toUrl(), LtiRequest::LTI_VERSION_1P3, []);
    }
}
