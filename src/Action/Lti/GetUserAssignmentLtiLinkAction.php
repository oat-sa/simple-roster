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

use App\Entity\Assignment;
use App\Entity\User;
use App\Exception\AssignmentNotFoundException;
use App\Exception\AssignmentNotProcessableException;
use App\Lti\Service\GetUserAssignmentLtiRequestService;
use App\Responder\SerializerResponder;
use Doctrine\ORM\EntityManagerInterface;
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

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        SerializerResponder $responder,
        GetUserAssignmentLtiRequestService $getUserAssignmentLtiRequestService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->responder = $responder;
        $this->getUserAssignmentLtiRequestService = $getUserAssignmentLtiRequestService;
        $this->entityManager = $entityManager;
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
            }

            $this->entityManager->persist($assignment);
            $this->entityManager->flush();

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
        } catch (AssignmentNotProcessableException $exception) {
            throw new ConflictHttpException($exception->getMessage());
        }
    }
}
