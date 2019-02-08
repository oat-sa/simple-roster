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

        var_dump(json_decode($client->getResponse()->getContent(), true));
    }

}
