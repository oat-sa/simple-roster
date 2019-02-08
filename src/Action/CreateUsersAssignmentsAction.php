<?php declare(strict_types=1);

namespace App\Action;

use App\Http\RequestEntityTooLargeHttpException;
use App\Repository\UserRepository;
use App\Responder\SerializerResponder;
use App\Service\CreateUsersAssignmentsService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateUsersAssignmentsAction
{
    public const LIMIT = 1000;

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
     * @throws RequestEntityTooLargeHttpException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $users = [];
        $resultOfNonExistingUsers = [];
        $usernames = $this->getUsernamesFromRequest($request);
        foreach ($usernames as $username) {
            try {
                $users[] = $this->userRepository->getByUsernameWithAssignments($username);
            } catch (EntityNotFoundException $exception) {
                $resultOfNonExistingUsers[$username] = 'failure';
                continue;
            }
        }

        $result = array_merge($resultOfNonExistingUsers, $this->createUsersAssignmentsService->create(...$users));

        return $this->responder->createJsonResponse(
            array_replace(
                array_flip($usernames),
                $result
            ),
            Response::HTTP_CREATED
        );
    }

    /**
     * @throws BadRequestHttpException
     * @throws RequestEntityTooLargeHttpException
     */
    private function getUsernamesFromRequest(Request $request): array
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

        if (count($usernames) > self::LIMIT) {
            throw new RequestEntityTooLargeHttpException(
                sprintf(
                    'User limit has been exceeded. Maximum of `%s` users are allowed per request.',
                    self::LIMIT
                )
            );
        }

        return $usernames;
    }
}
