<?php

namespace App\Tests\Functional\Action\Lti;

use App\Entity\Assignment;
use App\Entity\User;
use App\Generator\NonceGenerator;
use App\Lti\Request\LtiRequest;
use App\Repository\UserRepository;
use App\Security\OAuth\OAuthContext;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Carbon\Carbon;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetUserAssignmentLtiLinkActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    public function testItReturns401IfNotAuthenticated(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns405IfHttpMethodIsNotAllowed(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns404IfAssignmentDoesNotBelongToAuthenticatedUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $client = static::createClient();

        $this->logInAs($user, $client);

        $client->request('GET', '/api/v1/assignments/2/lti-link');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $this->assertArraySubset(
            [
                'error' => [
                    'message' => "Assignment id '2' not found for user 'user1'.",
                ],
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    public function testItReturnsLtiLinkAndUpdatedAssignmentStateToStarted(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0, new DateTimeZone('UTC')));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $client = static::createClient();

        $this->logInAs($user, $client);

        $client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            [
                'ltiLink' => 'http://lti-director.com',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'myKey',
                    'oauth_nonce' => (new NonceGenerator())->generate(),
                    'oauth_signature' => 'o218FKyyUML7L8jE6s9s5qrZnZY=',
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
            json_decode($client->getResponse()->getContent(), true)
        );

        /** @var Assignment $assignment */
        $assignment = $this->getRepository(Assignment::class)->find(1);
        $this->assertEquals(Assignment::STATE_STARTED, $assignment->getState());
    }
}
