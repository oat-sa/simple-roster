<?php declare(strict_types=1);

namespace App\Action;

use App\Entity\User;
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

    public function __construct(SerializerResponder $responder, GetUserAssignmentLtiLinkService $getUserAssignmentLtiLinkService)
    {
        $this->responder = $responder;
    }

    /**
     * @param UserInterface|User $user
     */
    public function __invoke(UserInterface $user, int $assignmentId): Response
    {
        $link = $this->getUserAssignmentLtiLinkService->getAssignmentLtiLink(
            $user->getAvailableAssignmentById($assignmentId)
        );

        return $this->responder->createJsonResponse([]);
    }
}
