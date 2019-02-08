<?php declare(strict_types=1);

namespace App\Action;

use App\Entity\Assignment;
use App\Entity\User;
use App\Exception\AssignmentNotFoundException;
use App\Responder\SerializerResponder;
use App\Service\GetUserAssignmentLtiLinkService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserAssignmentLtiLinkAction
{
    /** @var SerializerResponder */
    private $responder;

    /** @var GetUserAssignmentLtiLinkService */
    private $getUserAssignmentLtiLinkService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        SerializerResponder $responder,
        GetUserAssignmentLtiLinkService $getUserAssignmentLtiLinkService,
        EntityManagerInterface $entityManager
    ) {
        $this->responder = $responder;
        $this->getUserAssignmentLtiLinkService = $getUserAssignmentLtiLinkService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param UserInterface|User $user
     */
    public function __invoke(UserInterface $user, int $assignmentId): Response
    {
        try {
            $assignment = $user->getAvailableAssignmentById($assignmentId);
            $ltiRequest = $this->getUserAssignmentLtiLinkService->getAssignmentLtiRequest($assignment);

            $assignment->setState(Assignment::STATE_STARTED);
            $this->entityManager->flush();

            return $this->responder->createJsonResponse($ltiRequest);
        } catch (AssignmentNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
    }
}
