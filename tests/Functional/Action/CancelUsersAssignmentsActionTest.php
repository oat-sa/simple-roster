<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Action\CancelUsersAssignmentsAction;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CancelUsersAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    private const CANCEL_USERS_ASSIGNMENTS_URI = '/api/v1/assignments';

    public function testItThrowsUnauthorizedHttpExceptionIfUserIsNotAuthenticated(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_DELETE, self::CANCEL_USERS_ASSIGNMENTS_URI);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Full authentication is required to access this resource.',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testitThrowsBadRequestHttpExceptionIfInvalidRequestBodyReceived(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(
            Request::METHOD_DELETE,
            self::CANCEL_USERS_ASSIGNMENTS_URI,
            [],
            [],
            [],
            'invalid body content'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Invalid JSON request body received. Error: Syntax error',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testItThrowsBadRequestHttpExceptionIfEmptyRequestBodyReceived(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $client->request(
            Request::METHOD_DELETE,
            self::CANCEL_USERS_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Empty request body received.',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testItThrowsRequestEntityTooLargeHttpExceptionIfRequestPayloadIsTooLarge(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client = self::createClient();

        $this->logInAs($user, $client);

        $requestPayload = [];
        for ($i = 0; $i <= CancelUsersAssignmentsAction::LIMIT + 1; $i++) {
            $requestPayload[] = 'user_' . $i;
        }

        $client->request(
            Request::METHOD_DELETE,
            self::CANCEL_USERS_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode($requestPayload)
        );

        $this->assertEquals(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'User limit has been exceeded. Maximum of `1000` users are allowed per request.',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testSuccessfulResponse(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');
        $client  = self::createClient();

        $this->logInAs($user, $client);

        $client->request(
            Request::METHOD_DELETE,
            self::CANCEL_USERS_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([$user->getUsername(), 'nonExistingUser1', 'nonExistingUser2'])
        );

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertEquals([
            $user->getUsername() => true,
            'nonExistingUser1' => false,
            'nonExistingUser2' => false,
        ], json_decode($client->getResponse()->getContent(), true));

        // Refresh repository
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->assertEmpty($user->getAvailableAssignments());
    }
}
