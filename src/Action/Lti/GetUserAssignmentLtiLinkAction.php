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

namespace OAT\SimpleRoster\Action\Lti;

use OAT\SimpleRoster\Entity\Assignment;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\AssignmentNotFoundException;
use OAT\SimpleRoster\Exception\AssignmentUnavailableException;
use OAT\SimpleRoster\Exception\InvalidAssignmentStatusTransitionException;
use OAT\SimpleRoster\Lti\Factory\LtiRequestFactoryInterface;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Responder\SerializerResponder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserAssignmentLtiLinkAction
{
    private SerializerResponder $responder;
    private LtiRequestFactoryInterface $ltiRequestFactory;
    private AssignmentRepository $assignmentRepository;
    private LoggerInterface $logger;

    public function __construct(
        SerializerResponder $responder,
        LtiRequestFactoryInterface $ltiRequestFactory,
        AssignmentRepository $assignmentRepository,
        LoggerInterface $logger
    ) {
        $this->responder = $responder;
        $this->ltiRequestFactory = $ltiRequestFactory;
        $this->assignmentRepository = $assignmentRepository;
        $this->logger = $logger;
    }

    public function __invoke(UserInterface $user, string $assignmentId): Response
    {
        try {
            /** @var User $user */
            $assignment = $user->getAvailableAssignmentById($assignmentId);
            $ltiRequest = $this->ltiRequestFactory->create($assignment);

            if ($assignment->getStatus() !== Assignment::STATUS_STARTED) {
                $assignment->start();

                $this->assignmentRepository->flush();
            }

            $this->logger->info(
                sprintf("LTI request was successfully generated for assignment with id='%s'", $assignmentId),
                [
                    'ltiRequest' => $ltiRequest,
                    'lineItem' => $assignment->getLineItem(),
                ]
            );

            return $this->responder->createJsonResponse($ltiRequest);
        } catch (AssignmentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (AssignmentUnavailableException | InvalidAssignmentStatusTransitionException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }
    }
}
