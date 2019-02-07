<?php declare(strict_types=1);

namespace App\Tests\Functional\Action;

use App\Entity\User;
use App\Tests\Traits\DatabaseFixturesTrait;
use App\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutActionTest extends WebTestCase
{
    use DatabaseFixturesTrait;
    use UserAuthenticatorTrait;

    public function testItLogsOutProperlyTheUser()
    {
        $client = self::createClient();

        $user = $this->getRepository(User::class)->find(1);

        $this->logInAs($user, $client);

        $session = $client->getContainer()->get('session');

        $this->assertNotEmpty($session->all());

        $client->request(
            Request::METHOD_POST,
            '/api/v1/auth/logout',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'])
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $client->getResponse()->getStatusCode());

        $this->assertEmpty($session->all());
    }
}
