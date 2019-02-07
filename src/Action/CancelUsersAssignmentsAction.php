<?php declare(strict_types=1);

namespace App\Action;

use App\Repository\UserRepository;
use App\Responder\SerializerResponder;
use App\Service\CancelUsersAssignmentsService;
use Doctrine\ORM\EntityNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CancelUsersAssignmentsAction
{
    /** @var CancelUsersAssignmentsService */
    private $cancelUsersAssignmentsService;

    /** @var UserRepository */
    private $userRepository;

    /** @var SerializerResponder */
    private $responder;

    public function __construct(
        CancelUsersAssignmentsService $cancelUsersAssignmentsService,
        UserRepository $userRepository,
        SerializerResponder $responder
    ) {
        $this->cancelUsersAssignmentsService = $cancelUsersAssignmentsService;
        $this->userRepository = $userRepository;
        $this->responder = $responder;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     * @throws Exception
     */
    public function __invoke(Request $request): JsonResponse
    {
        $usernames = json_decode($request->getContent(), true);
        if (json_last_error()) {
            throw new BadRequestHttpException(
                sprintf(
                    'Invalid JSON request body received. Error: %s',
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

            $this->cancelUsersAssignmentsService->cancel(...$users);
        } catch (EntityNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }

        return $this->responder->createJsonResponse(null);
    }
}
