<?php declare(strict_types=1);

namespace App\Action;

use App\Entity\User;
use App\Exception\AssignmentNotFoundException;
use App\Responder\SerializerResponder;
use App\Service\GetUserAssignmentLtiLinkService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserAssignmentLtiLinkAction
{
    /** @var SerializerResponder */
    private $responder;

    /** @var GetUserAssignmentLtiLinkService */
    private $getUserAssignmentLtiLinkService;

    public function __construct(
        SerializerResponder $responder,
        GetUserAssignmentLtiLinkService $getUserAssignmentLtiLinkService
    ) {
        $this->responder = $responder;
        $this->getUserAssignmentLtiLinkService = $getUserAssignmentLtiLinkService;
    }

    /**
     * @param UserInterface|User $user
     * @throws AssignmentNotFoundException
     */
    public function __invoke(UserInterface $user, int $assignmentId): Response
    {
        $link = $this->getUserAssignmentLtiLinkService->getAssignmentLtiLink(
            $user->getAvailableAssignmentById($assignmentId)
        );

        return $this->responder->createJsonResponse(['link' => $link]);
    }
}
