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
use OAT\SimpleRoster\Exception\AssignmentNotProcessableException;
use OAT\SimpleRoster\Exception\AssignmentUnavailableException;
use OAT\SimpleRoster\Lti\Service\GetUserAssignmentLtiRequestService;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Responder\SerializerResponder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserAssignmentLtiLinkAction
{
    /** @var SerializerResponder */
    private $responder;

    /** @var GetUserAssignmentLtiRequestService */
    private $getUserAssignmentLtiRequestService;

    /** @var AssignmentRepository */
    private $assignmentRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        SerializerResponder $responder,
        GetUserAssignmentLtiRequestService $getUserAssignmentLtiRequestService,
        AssignmentRepository $assignmentRepository,
        LoggerInterface $logger
    ) {
        $this->responder = $responder;
        $this->getUserAssignmentLtiRequestService = $getUserAssignmentLtiRequestService;
        $this->assignmentRepository = $assignmentRepository;
        $this->logger = $logger;
    }

    /**
     * @param User $user
     */
    public function __invoke(UserInterface $user, int $assignmentId): Response
    {
        try {
            $assignment = $user->getAvailableAssignmentById($assignmentId);
            $ltiRequest = $this->getUserAssignmentLtiRequestService->getAssignmentLtiRequest($assignment);

            if ($assignment->getState() !== Assignment::STATE_STARTED) {
                $assignment
                    ->setState(Assignment::STATE_STARTED)
                    ->incrementAttemptsCount();

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
            throw new NotFoundHttpException($exception->getMessage());
        } catch (AssignmentUnavailableException | AssignmentNotProcessableException $exception) {
            throw new ConflictHttpException($exception->getMessage());
        }
    }
}
