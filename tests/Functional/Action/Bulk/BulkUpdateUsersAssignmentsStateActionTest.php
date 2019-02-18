<?php declare(strict_types=1);

namespace App\Tests\Functional\Action\Bulk;

use App\Entity\Assignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Request\ParamConverter\BulkOperationCollectionParamConverter;
use App\Tests\Traits\DatabaseFixturesTrait;
use Carbon\Carbon;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkUpdateUsersAssignmentsStateActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    /** @var Client */
    private $client;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpFixtures();

        $this->client = self::createClient();
    }

    public function testItThrowsBadRequestHttpExceptionIfInvalidRequestBodyReceived(): void
    {
        $this->client->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
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

    public function testItThrowsBadRequestHttpExceptionIfEmptyRequestBodyReceived(): void
    {
        $this->client->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
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

    public function testItThrowsRequestEntityTooLargeHttpExceptionIfRequestPayloadIsTooLarge(): void
    {
        $this->client->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload(range(0, BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT + 1))
        );

        $this->assertEquals(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => sprintf(
                        "Bulk operation limit has been exceeded, maximum of '%s' allowed per request.",
                        BulkOperationCollectionParamConverter::BULK_OPERATIONS_LIMIT
                    )
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testItDoesNotUpdateAssignmentsStateWithInvalidUsersProvided(): void
    {
        Carbon::setTestNow(new DateTime('2019-01-01 00:00:00'));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->client->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([
                $user->getUsername(),
                'nonExistingUser1',
            ])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'data' => [
                    'applied' => false,
                    'results' => [
                        $user->getUsername() => true,
                        'nonExistingUser1' => false,
                    ]
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->assertCount(1, $this->getRepository(Assignment::class)->findAll());

        $this->getEntityManager()->refresh($user->getLastAssignment());
        $this->assertEquals(Assignment::STATE_READY, $user->getLastAssignment()->getState());
        $this->assertCount(1, $user->getAvailableAssignments());
    }

    public function testItUpdatesAssignmentStateWithValidUserProvided(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->client->request(
            Request::METHOD_PATCH,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'data' => [
                    'applied' => true,
                    'results' => [
                        $user->getUsername() => true,
                    ]
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->assertCount(1, $this->getRepository(Assignment::class)->findAll());

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $reloadedUser = $userRepository->getByUsernameWithAssignments('user1');

        $this->assertEquals(Assignment::STATE_CANCELLED, $reloadedUser->getLastAssignment()->getState());
        $this->assertCount(0, $reloadedUser->getAvailableAssignments());
    }

    private function generateRequestPayload(array $users): string
    {
        $payload = [];

        foreach ($users as $user) {
            $payload[] = [
                'identifier' => $user,
                'attributes' => [
                    'state' => Assignment::STATE_CANCELLED
                ]
            ];
        }

        return json_encode($payload);
    }
}
