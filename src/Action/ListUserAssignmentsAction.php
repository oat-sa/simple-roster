<?php declare(strict_types=1);

namespace App\Action;

use App\Entity\User;
use App\Responder\SerializerResponder;
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
     * @param UserInterface|User $user
     */
    public function __invoke(UserInterface $user)
    {
        return $this->responder->createJsonResponse(['assignments' => $user->getAvailableAssignments()]);
    }
}
