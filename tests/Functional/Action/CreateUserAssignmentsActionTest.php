<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateUserAssignmentsActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    /** @var Client */
    private $client;

    private const CREATE_USER_ASSIGNMENTS_URI = '/api/v1/assignments';

    protected function setUp()
    {
        parent::setUp();

        $this->client = self::createClient();
    }

    public function testResponseForNonAuthenticatedUser(): void
    {
        $this->client->request(Request::METHOD_POST, self::CREATE_USER_ASSIGNMENTS_URI);

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testUserCanLogIn(): void
    {
        $this->client->request(
            Request::METHOD_POST,
            self::CREATE_USER_ASSIGNMENTS_URI,
            [],
            [],
            [],
            json_encode([
                'user' => 'user1',
                'password' => 'password',
            ])
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
