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

class LtiInstanceUpdateAction
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

    /**
     * @param LtiInstance[] $ltiInstances
     */
    protected function checkUniqueness(array $ltiInstances, LtiInstance $ltiInstance): bool
    {
        foreach ($ltiInstances as $instance) {
            if ($instance->getId() !== $ltiInstance->getId()) {
                return false;
            }
        }

        return true;
    }

    public function __invoke(Request $updateActionRequest, string $ltiInstanceId): Response
    {
        $this->validator->validate($updateActionRequest);

        $updateActionContent = json_decode(
            $updateActionRequest->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        /** @var LtiInstance|null $ltiInstance */
        $ltiInstance = $this->repository->find($ltiInstanceId);

        if ($ltiInstance === null) {
            return $this->serializer->error('Not found.', Response::HTTP_NOT_FOUND);
        }

        $criteria = (new LtiInstanceCriteria())
            ->addLtiLinks($updateActionContent['lti_link'])
            ->addLtiLabels($updateActionContent['label']);
        $existedLtiInstances = $this->repository->findAllByCriteria($criteria);

        if (!$this->checkUniqueness($existedLtiInstances, $ltiInstance)) {
            return $this->serializer->error(
                'LtiInstance with provided link or label already exists.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $ltiInstance->setLabel($updateActionContent['label']);
        $ltiInstance->setLtiLink($updateActionContent['lti_link']);
        $ltiInstance->setLtiKey($updateActionContent['lti_key']);
        $ltiInstance->setLtiSecret($updateActionContent['lti_secret']);

        $this->repository->persist($ltiInstance);
        $this->repository->flush();

        $this->eventDispatcher->dispatch(new LtiInstanceUpdatedEvent(), LtiInstanceUpdatedEvent::NAME);

        return $this->serializer->createJsonFromInstance($ltiInstance, Response::HTTP_ACCEPTED);
    }
}
