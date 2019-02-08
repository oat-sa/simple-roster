<?php

namespace App\Tests\Functional\Action;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GetUserAssignmentLtiLinkActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    public function testItReturns401IfNotAuthenticated()
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns405IfHttpMethodIsNotAllowed()
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testItReturns404IfAssignmentDoesNotBelongToAuthenticatedUser()
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

    public function testItReturnsLtiLink()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->getByUsernameWithAssignments('user1');

        $client = static::createClient();

        $this->logInAs($user, $client);

        $client->request('GET', '/api/v1/assignments/1/lti-link');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        var_dump(json_decode($client->getResponse()->getContent(), true));
        $this->assertEquals(
            [
                'ltiLink' => 'http://lti-director.com',
                'ltiParams' => [
                    'oauth_body_hash' => '',
                    'oauth_consumer_key' => 'key1',
                    'oauth_nonce' => '5c5d419fa7a30',
                    'oauth_signature' => 'qGkYbqOYHG8ybSMH28De6BA7+n0=',
                    'oauth_signature_method' => 'HMAC-SHA1',
                    'oauth_timestamp' => '1549615519',
                    'oauth_version' => '1.0',
                    'lti_message_type' => 'basic-lti-launch-request',
                    'lti_version' => 'basic-lti-launch-request',
                    'context_id' => 1,
                    'context_label' => 'gra13_ita_1',
                    'context_title' => 'label1',
                    'context_type' => 'CourseSection',
                    'roles' => 'Learner',
                    'user_id' => 1,
                    'resource_link_id' => 1234
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

}
