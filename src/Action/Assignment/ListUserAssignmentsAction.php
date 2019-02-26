<?php declare(strict_types=1);

namespace App\Action\Assignment;

use App\Entity\User;
use App\Responder\SerializerResponder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class ListUserAssignmentsAction
{
    /** @var SerializerResponder */
    private $responder;

    public function __construct(SerializerResponder $responder)
    {
        $this->responder = $responder;
    }

    /**
     * @param User $user
     */
    public function __invoke(UserInterface $user): Response
    {
        return $this->responder->createJsonResponse(['assignments' => $user->getAvailableAssignments()]);
    }
}
