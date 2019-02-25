<?php declare(strict_types=1);

namespace App\Tests\Functional\Action\Lti;

use App\Entity\Assignment;
use App\Entity\User;
use App\Generator\NonceGenerator;
use App\Lti\Request\LtiRequest;
use App\Repository\UserRepository;
use App\Security\OAuth\OAuthContext;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\LoggerTestingTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Carbon\Carbon;
use DateTimeZone;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetUserAssignmentLtiLinkActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;
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

    public function testItReturns401IfNotAuthenticated(): void
    {
        $this->client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testItReturns405IfHttpMethodIsNotAllowed(): void
    {
        $this->client->request('POST', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $this->client->getResponse()->getStatusCode());
    }

    public function testItReturns404IfAssignmentDoesNotBelongToAuthenticatedUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->client);

        $this->client->request('GET', '/api/v1/assignments/2/lti-link');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => "Assignment id '2' not found for user 'user1'.",
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testItReturns409IfAssignmentDoesNotHaveASuitableState(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $user->getLastAssignment()->setState(Assignment::STATE_COMPLETED);
        $this->getEntityManager()->flush();

        $this->logInAs($user, $this->client);

        $this->client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_CONFLICT, $this->client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => "Assignment with id '1' does not have a suitable state.",
                ],
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    public function testItReturnsLtiLinkAndUpdatedAssignmentStateToStarted(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->client);

        $this->client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'ltiLink' => 'http://lti-director.com/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'hnGSz3IWyuQwwYbQNvx+3mnvSvo=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => 1,
                    'context_label' => 'lineItemSlug',
                    'context_title' => 'The first line item',
                    'context_type' => LtiRequest::LTI_CONTEXT_TYPE,
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 1,
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html'
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $this->assertEquals(Assignment::STATE_STARTED, $assignment->getState());
    }

    public function testItReturnsLoadBalancedLtiLinkAndUpdatedAssignmentStateToStarted(): void
    {
        $_ENV['LTI_ENABLE_INSTANCES_LOAD_BALANCER'] = true;

        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $this->logInAs($user, $this->client);

        $this->client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'ltiLink' => 'http://lb_infra_2/eyJkZWxpdmVyeSI6Imh0dHA6XC9cL2xpbmVpdGVtdXJpLmNvbSJ9',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'vAeVvGcxolp529UcrtUV5IMh+Yo=',
                    'oauth_signature_method' => OAuthContext::METHOD_MAC_SHA1,
                    'oauth_timestamp' => Carbon::getTestNow()->getTimestamp(),
                    'oauth_version' => OAuthContext::VERSION_1_0,
                    'lti_message_type' => LtiRequest::LTI_MESSAGE_TYPE,
                    'lti_version' => LtiRequest::LTI_VERSION,
                    'context_id' => 1,
                    'context_label' => 'lineItemSlug',
                    'context_title' => 'The first line item',
                    'context_type' => LtiRequest::LTI_CONTEXT_TYPE,
                    'roles' => LtiRequest::LTI_ROLE,
                    'user_id' => 1,
                    'lis_person_name_full' => 'user1',
                    'resource_link_id' => 1,
                    'lis_outcome_service_url' => 'http://localhost/api/v1/lti/outcome',
                    'lis_result_sourcedid' => 1,
                    'launch_presentation_return_url' => 'http://example.com/index.html'
                ]
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $this->assertEquals(Assignment::STATE_STARTED, $assignment->getState());
    }

    public function testItLogsSuccessfulLtiRequestCreation(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);

        $this->logInAs($user, $this->client);

        $this->client->request('GET', '/api/v1/assignments/1/lti-link');
        $ltiRequestInResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($this->getLogRecords()[0]['context']['lineItem'], $assignment->getLineItem());
        $this->assertHasRecordThatPasses(
            function (array $record) use ($assignment, $ltiRequestInResponse) {
                /** @var LtiRequest $ltiRequest */
                $ltiRequest = $record['context']['ltiRequest'];

                return
                    $record['message'] === "LTI request was successfully generated for assignment with id='1'"
                    && $ltiRequest->jsonSerialize() === $ltiRequestInResponse
                    && $record['context']['lineItem'] === $assignment->getLineItem();
            },
            Logger::INFO
        );
    }
}
