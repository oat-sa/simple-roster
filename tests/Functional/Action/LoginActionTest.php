<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;

    public function testItFailsWithWrongCredentials()
    {
        $client = self::createClient();

        $client->request(
            Request::METHOD_POST,
            '/api/v1/auth/login',
            [],
            [],
            [],
            json_encode(['username' => 'invalid', 'password' => 'invalid'])
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testItLogsInProperlyTheUser()
    {
        $client = self::createClient();

        $client->request(
            Request::METHOD_POST,
            '/api/v1/auth/login',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'])
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->assertArrayHasKey('set-cookie', $client->getResponse()->headers->all());

        $session = $client->getContainer()->get('session');

        $this->assertNotEmpty($session->all());
    }
}
