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
use OAT\Library\Lti1p3Core\Message\Payload\Claim\BasicOutcomeClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\LaunchPresentationClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Lti\Builder\Lti1p3MessageBuilder;
use OAT\SimpleRoster\Lti\Configuration\LtiConfiguration;
use OAT\SimpleRoster\Lti\Exception\RegistrationNotFoundException;
use OAT\SimpleRoster\Lti\Request\LtiRequest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Lti1p3RequestFactory implements LtiRequestFactoryInterface
{
    /** @var RegistrationRepositoryInterface */
    private $registrationRepository;

    /** @var Lti1p3MessageBuilder */
    private $ltiMessageBuilder;

    /** @var LtiConfiguration */
    private $ltiConfiguration;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        RegistrationRepositoryInterface $registrationRepository,
        Lti1p3MessageBuilder $ltiMessageBuilder,
        LtiConfiguration $ltiConfiguration,
        RouterInterface $router
    ) {
        $this->registrationRepository = $registrationRepository;
        $this->ltiMessageBuilder = $ltiMessageBuilder;
        $this->ltiConfiguration = $ltiConfiguration;
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

        $message = $this->ltiMessageBuilder
            ->withMessagePayloadClaim(
                new BasicOutcomeClaim(
                    (string)$assignment->getId(),
                    $this->router->generate(
                        'updateLti1p3Outcome',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                )
            )
            ->withMessagePayloadClaim(
                new LaunchPresentationClaim(
                    null,
                    null,
                    null,
                    $this->ltiConfiguration->getLtiLaunchPresentationReturnUrl(),
                    $this->ltiConfiguration->getLtiLaunchPresentationLocale(),
                )
            )
            ->withMessagePayloadClaim(
                new ContextClaim(
                    (string)$assignment->getId(),
                    [],
                    $assignment->getLineItem()->getSlug(),
                    $assignment->getLineItem()->getLabel(),
                )
            )
            ->build($registration, $assignment);

        return new LtiRequest($message->toUrl(), LtiRequest::LTI_VERSION_1P3, []);
    }
}
