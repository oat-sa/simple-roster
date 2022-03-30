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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Action\LtiInstance;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Events\LtiInstanceUpdatedEvent;
use OAT\SimpleRoster\Repository\Criteria\LtiInstanceCriteria;
use OAT\SimpleRoster\Repository\LtiInstanceRepository;
use OAT\SimpleRoster\Request\Validator\LtiInstance\LtiInstanceValidator;
use OAT\SimpleRoster\Responder\LtiInstance\LtiInstanceSerializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as EventDispatcher;

class LtiInstanceCreateAction
{
    private LtiInstanceRepository $repository;
    private LtiInstanceSerializer $serializer;
    private EventDispatcher $eventDispatcher;
    private LtiInstanceValidator $validator;

    public function __construct(
        LtiInstanceRepository $repository,
        LtiInstanceSerializer $serializer,
        EventDispatcher $eventDispatcher,
        LtiInstanceValidator $validator
    ) {
        $this->repository = $repository;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
    }

    public function __invoke(Request $createActionRequest): Response
    {
        $this->validator->validate($createActionRequest);

        $createActionContent = json_decode(
            $createActionRequest->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $criteria = (new LtiInstanceCriteria())
            ->addLtiLinks($createActionContent['lti_link'])
            ->addLtiLabels($createActionContent['label']);
        $existedLtiInstances = $this->repository->findAllByCriteria($criteria);

        if (count($existedLtiInstances) > 0) {
            return $this->serializer->error(
                'LtiInstance with provided link or label already exists.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $ltiInstance = new LtiInstance(
            0,
            $createActionContent['label'],
            $createActionContent['lti_link'],
            $createActionContent['lti_key'],
            $createActionContent['lti_secret'],
        );

        $this->repository->persist($ltiInstance);
        $this->repository->flush();

        $this->eventDispatcher->dispatch(new LtiInstanceUpdatedEvent(), LtiInstanceUpdatedEvent::NAME);

        return $this->serializer->createJsonFromInstance($ltiInstance, Response::HTTP_ACCEPTED);
    }
}
