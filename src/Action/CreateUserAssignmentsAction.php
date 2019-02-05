<?php declare(strict_types=1);

namespace App\Action;

use App\Repository\UserRepository;
use App\Responder\SerializerResponder;
use App\Service\CreateUsersAssignmentsService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateUserAssignmentsAction
{
    /** @var CreateUsersAssignmentsService */
    private $createUsersAssignmentsService;

    /** @var UserRepository */
    private $userRepository;

    /** @var SerializerResponder */
    private $responder;

    public function __construct(
        CreateUsersAssignmentsService $createUsersAssignmentsService,
        UserRepository $userRepository,
        SerializerResponder $responder
    ) {
        $this->createUsersAssignmentsService = $createUsersAssignmentsService;
        $this->userRepository = $userRepository;
        $this->responder = $responder;
    }

    /**
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(Request $request)
    {
        $usernames = json_decode($request->getContent(), true);
        if (json_last_error()) {
            throw new BadRequestHttpException(
                sprintf(
                    'An error occurred while decoding request body: %s',
                    json_last_error_msg()
                )
            );
        }

        if (empty($usernames)) {
            throw new BadRequestHttpException('Empty request body received.');
        }

        $users = [];
        try {
            foreach ($usernames as $username) {
                $users[] = $this->userRepository->getByUsernameWithAssignments($username);
            }
        } catch (EntityNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return $this->responder->createJsonResponse(
            ['assignments' => $this->createUsersAssignmentsService->create(...$users)],
            Response::HTTP_CREATED
        );
    }
}
