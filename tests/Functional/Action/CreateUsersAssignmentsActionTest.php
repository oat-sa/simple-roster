<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Action\CreateUsersAssignmentsAction;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateUsersAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    /** @var Client */
    private $client;

    /** @var UserRepository */
    private $userRepository;

    private const CREATE_USER_ASSIGNMENTS_URI = '/api/v1/assignments';

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpFixtures();

        $this->client = self::createClient();
        $this->userRepository = $this->getRepository(User::class);
    }

    public function testItThrowsUnauthorizedHttpExceptionIfUserIsNotAuthenticated(): void
    {
        $this->client->request(Request::METHOD_POST, self::CREATE_USER_ASSIGNMENTS_URI);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Full authentication is required to access this resource.',
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testItThrowsBadRequestHttpExceptionIfRequestBodyIsInvalid(): void
    {
        $user = $this->userRepository->getByUsernameWithAssignments('user1');
        $this->logInAs($user, $this->client);

        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            'invalid body content'
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Invalid JSON request body received. Error: Syntax error',
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testItThrowsRequestEntityTooLargeHttpExceptionIfRequestPayloadIsTooLarge(): void
    {
        $user = $this->userRepository->getByUsernameWithAssignments('user1');
        $this->logInAs($user, $this->client);

        $requestPayload = [];
        for ($i = 0; $i <= CreateUsersAssignmentsAction::LIMIT + 1; $i++) {
            $requestPayload[] = 'user_' . $i;
        }

        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode($requestPayload)
        );

        $this->assertEquals(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'User limit has been exceeded. Maximum of `1000` users are allowed per request.',
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testItThrowsBadRequestHttpExceptionIfRequestPayloadIsValidButEmpty(): void
    {
        $user = $this->userRepository->getByUsernameWithAssignments('user1');
        $this->logInAs($user, $this->client);

        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([])
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'Empty request body received.',
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testSuccessfulResponse(): void
    {
        $user = $this->userRepository->getByUsernameWithAssignments('user1');
        $this->logInAs($user, $this->client);

        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([$user->getUsername(), 'nonExistingUser1', 'nonExistingUser2'])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertEquals([
            $user->getUsername() => true,
            'nonExistingUser1' => false,
            'nonExistingUser2' => false,
        ], json_decode($this->client->getResponse()->getContent(), true));
    }
}
