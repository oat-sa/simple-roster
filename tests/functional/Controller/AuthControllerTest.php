<?php

namespace App\Tests\Functional\Controller;

use App\Storage\InMemoryStorage;
use App\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class AuthControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    private $client = null;

    /**
     * @var string
     */
    private $plainPassword = ' qWeR_y123.{]}@.2 _,      ';

    public function setUp()
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
    }

    private function addUser()
    {
        $storage = new InMemoryStorage();
        $encoder = new MessageDigestPasswordEncoder('sha256');
        $salt = base64_encode(random_bytes(30));
        $encodedPassword = $encoder->encodePassword($this->plainPassword, $salt);

        $storage->insert('users', ['username' => 'user_1'], [
            'username' => 'user_1',
            'password' => $encodedPassword,
            'salt' => $salt,
            'assignments' => [],
        ]);
        return $storage;
    }

    public function testErrorCodeOnLogInWithMissingParams()
    {
        $this->client->request('POST', '/api/v1/auth/login', []);
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('Mandatory parameter', $this->client->getResponse()->getContent());

        $this->client->request('POST', '/api/v1/auth/login', ['login' => '']);
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringStartsWith('Mandatory parameter', $this->client->getResponse()->getContent());
    }

    public function testAuthWorks()
    {
        $storage = $this->addUser();
        $this->client->getContainer()->set(StorageInterface::class, $storage);
        $this->client->request('POST', '/api/v1/auth/login', ['login' => 'user_1', 'password' => $this->plainPassword]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAuthFailsIfPasswordInDifferentCase()
    {
        $storage = $this->addUser();
        $this->client->getContainer()->set(StorageInterface::class, $storage);
        $this->client->request('POST', '/api/v1/auth/login', ['login' => 'user_1', 'password' => strtoupper($this->plainPassword)]);
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    }

    public function testRepeatedAttemptOfAuthenticationFails()
    {
        $storage = $this->addUser();
        $this->client->getContainer()->set(StorageInterface::class, $storage);
        $this->client->request('POST', '/api/v1/auth/login', ['login' => 'user_1', 'password' => $this->plainPassword]);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $this->client->request('POST', '/api/v1/auth/login', ['login' => 'user_1', 'password' => $this->plainPassword]);
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
    }

    public function testLoginAfterLogout()
    {
        $storage = $this->addUser();
        $this->client->getContainer()->set(StorageInterface::class, $storage);

        $this->client->request('POST', '/api/v1/auth/login', ['login' => 'user_1', 'password' => $this->plainPassword]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('OK', $this->client->getResponse()->getContent());

        $this->client->request('POST', '/api/v1/auth/logout');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/v1/auth/logout');
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', '/api/v1/auth/login', ['login' => 'user_1', 'password' => $this->plainPassword]);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('OK', $this->client->getResponse()->getContent());
    }
}