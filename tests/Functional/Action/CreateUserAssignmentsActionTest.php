<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateUserAssignmentsActionTest extends WebTestCase
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

    public function testWithNonAuthenticatedUser(): void
    {
        $this->client->request(Request::METHOD_POST, self::CREATE_USER_ASSIGNMENTS_URI);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => 'A Token was not found in the TokenStorage.',
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testWithInvalidRequestBodyContent(): void
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

    public function testWithEmptyUsernameListInRequestBody(): void
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

    public function testWithNonExistingUser(): void
    {
        $user = $this->userRepository->getByUsernameWithAssignments('user1');
        $this->logInAs($user, $this->client);

        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([$user->getUsername(), 'nonExistingUsername'])
        );

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => "User with username = 'nonExistingUsername' cannot be found.",
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testIfNewAssignmentCanBeCreated(): void
    {
        $user = $this->userRepository->getByUsernameWithAssignments('user1');

        $expectedLineItem = $user->getLastAssignment()->getLineItem();
        $lastAssignmentId = $user->getLastAssignment()->getId();

        $this->logInAs($user, $this->client);

        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([$user->getUsername()])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $this->assertEquals([
            'assignments' => [
                [
                    'id' => $lastAssignmentId + 1,
                    'username' => $user->getUsername(),
                    'state' => Assignment::STATE_READY,
                    'lineItem' => [
                        'uri' => $expectedLineItem->getUri(),
                        'label' => $expectedLineItem->getLabel(),
                        'startDateTime' => $expectedLineItem->getStartAt()->getTimestamp(),
                        'endDateTime' => $expectedLineItem->getEndAt()->getTimestamp(),
                        'infrastructure' => $expectedLineItem->getInfrastructure()->getId(),
                    ],
                ],
            ],
        ], json_decode($this->client->getResponse()->getContent(), true));
    }
}
