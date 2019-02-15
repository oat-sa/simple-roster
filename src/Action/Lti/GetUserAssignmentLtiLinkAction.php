<?php declare(strict_types=1);

namespace App\Action\Lti;

use App\Entity\Assignment;
use App\Entity\User;
use App\Exception\AssignmentNotFoundException;
use App\Exception\AssignmentNotProcessableException;
use App\Responder\SerializerResponder;
use App\Service\GetUserAssignmentLtiRequestService;
use Doctrine\ORM\EntityManagerInterface;
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

    public function __construct(
        SerializerResponder $responder,
        GetUserAssignmentLtiRequestService $getUserAssignmentLtiRequestService,
        EntityManagerInterface $entityManager
    ) {
        $this->responder = $responder;
        $this->getUserAssignmentLtiRequestService = $getUserAssignmentLtiRequestService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param UserInterface|User $user
     */
    public function __invoke(UserInterface $user, int $assignmentId): Response
    {
        try {
            $assignment = $user->getAvailableAssignmentById($assignmentId);
            $ltiRequest = $this->getUserAssignmentLtiRequestService->getAssignmentLtiRequest($assignment);

            $assignment->setState(Assignment::STATE_STARTED);
            $this->entityManager->flush();

            return $this->responder->createJsonResponse($ltiRequest);
        } catch (AssignmentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        } catch (AssignmentNotProcessableException $exception) {
            throw new ConflictHttpException($exception->getMessage());
        }
    }
}
