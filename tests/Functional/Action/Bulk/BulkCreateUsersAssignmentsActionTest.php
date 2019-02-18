<?php declare(strict_types=1);

namespace App\Tests\Functional\Action\Assignment;

use App\Entity\Assignment;
use App\Entity\User;
use App\Request\ParamConverter\BulkOperationCollectionParamConverter;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\LoggerTestingTrait;
use Carbon\Carbon;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUsersAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use LoggerTestingTrait;

    /** @var Client */
    private $client;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->setUpFixtures();

        $this->client = self::createClient();

        $this->setUpTestLogHandler();
    }

    public function testItThrowsBadRequestHttpExceptionIfRequestBodyIsInvalid(): void
    {
        $this->client->request(
            Request::METHOD_POST,
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

    public function testItThrowsRequestEntityTooLargeHttpExceptionIfRequestPayloadIsTooLarge(): void
    {
        $this->client->request(
            Request::METHOD_POST,
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

    public function testItThrowsBadRequestHttpExceptionIfRequestPayloadIsValidButEmpty(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([])
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

    public function testItDoesNotCreateNewAssignmentsWithInvalidUsersProvided(): void
    {
        /** @var User $user */
        $user = $this->getRepository(User::class)->getByUsernameWithAssignments('user1');

        $this->client->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([
                $user->getUsername(),
                'nonExistingUser1',
            ])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

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
    }

    public function testItCreatesNewAssignmentsWithValidUserProvided(): void
    {
        Carbon::setTestNow(new DateTime('2019-01-01 00:00:00'));

        /** @var User $user */
        $user = $this->getRepository(User::class)->getByUsernameWithAssignments('user1');
        $lastAssignment = $user->getLastAssignment();

        $this->client->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());

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

        $this->assertCount(2, $this->getRepository(Assignment::class)->findAll());

        /** @var User $reloadedUser */
        $reloadedUser = $this->getRepository(User::class)->getByUsernameWithAssignments('user1');

        $this->assertEquals(Assignment::STATE_READY, $reloadedUser->getLastAssignment()->getState());
        $this->assertNotEquals($lastAssignment->getId(), $reloadedUser->getLastAssignment()->getId());
        $this->assertCount(1, $reloadedUser->getAvailableAssignments());
    }

    public function testItLogsSuccessfulBulkOperations(): void
    {
        Carbon::setTestNow(new DateTime('2019-01-01 00:00:00'));

        /** @var User $user */
        $user = $this->getRepository(User::class)->getByUsernameWithAssignments('user1');

        $this->client->request(
            Request::METHOD_POST,
            '/api/v1/bulk/assignments',
            [],
            [],
            [],
            $this->generateRequestPayload([$user->getUsername()])
        );

        $this->assertHasInfoLogRecordWithMessage(
            'Successful assignment create operation (id=`2`) for user with username=`user1`.'
        );
    }

    private function generateRequestPayload(array $users): string
    {
        $payload = [];

        foreach ($users as $user) {
            $payload[] = [
                'identifier' => $user
            ];
        }

        return json_encode($payload);
    }
}
