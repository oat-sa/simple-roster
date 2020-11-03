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

namespace App\Action\Lti;

use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ContextClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Lti13LaunchAction
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    public function __construct(RegistrationRepositoryInterface $repository, LtiResourceLinkLaunchRequestBuilder $builder)
    {
        $this->repository = $repository;
        $this->builder = $builder;
    }


    public function __invoke(Request $request): Response
    {
        $resourceLink = new LtiResourceLink('resourceIdentifier');

        $message = $this->builder->buildLtiResourceLinkLaunchRequest(
            $resourceLink,
            $this->repository->find('demo'),
            'loginHint',
            null,
            [
                'Learner'
            ],
            [
                'customClaim' => 'customValue',
                new ContextClaim('contextIdentifier', [], 'contextLabel')
            ]
        );

        return new Response($message->toHtmlLink('click me'));
    }
}
