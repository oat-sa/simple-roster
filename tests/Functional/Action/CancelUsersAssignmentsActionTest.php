<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

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

    public function testWithNonAuthenticatedUser(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_DELETE, self::CANCEL_USERS_ASSIGNMENTS_URI);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            [
                'error' => [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'message' => 'A Token was not found in the TokenStorage.',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testWithInvalidRequestBodyContent(): void
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
        $this->assertEquals(
            [
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Invalid JSON request body received. Error: Syntax error',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testWithEmptyUsernameListInRequestBody(): void
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
        $this->assertEquals(
            [
                'error' => [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Empty request body received.',
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testWithNonExistingUser(): void
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
            json_encode([$user->getUsername(), 'nonExistingUsername'])
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            [
                'error' => [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => "User with username = 'nonExistingUsername' cannot be found.",
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testIfAssignmentsCanBeCancelled(): void
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
            json_encode([$user->getUsername()])
        );

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        // Refresh repository
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->assertEmpty($user->getAvailableAssignments());
    }
}
