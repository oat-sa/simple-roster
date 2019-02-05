<?php

namespace App\Tests;

use App\Model\User;
use App\ODM\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseWebTestCase extends WebTestCase
{
    /**
     * @var Client
     */
    private $client;

    public function getUrlsForMethodNotAllowed()
    {
        return [];
    }

    /**
     * @dataProvider getUrlsForMethodNotAllowed
     */
    public function testMethodNotAllowedForLogin(string $excludedMethod, string $url)
    {
        switch ($excludedMethod) {
            case 'GET':
                $this->getJson($url);
                break;

            case 'PUT':
                $this->putJson($url, '');
                break;

            default:
                throw new \RuntimeException('Method '. $excludedMethod .' not included yet');
        }

        $this->assertEquals(405, $this->getClient()->getResponse()->getStatusCode());

        $this->assertApiProblemResponse(405, 'Method Not Allowed');
    }

    public function setUp()
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    protected function getClient(): Client
    {
        return $this->client;
    }

    protected function logInAs(UserInterface $user, $role = 'ROLE_USER'): void
    {
        $session = $this->getClient()->getContainer()->get('session');

        // the firewall context defaults to the firewall name
        $firewallContext = 'api';

        $token = new UsernamePasswordToken($user, null, $firewallContext, array($role));
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->getClient()->getCookieJar()->set($cookie);
    }

    protected function addUser(string $username, string $plainPassword, array $assignments = []): User
    {
        $storage = self::$container->get(StorageInterface::class);

        $pwdEncoder = self::$container->get(UserPasswordEncoderInterface::class);

        $user = new User($username);

        $encodedPassword = $pwdEncoder->encodePassword($user, $plainPassword);

        $user->setPassword($encodedPassword);

        $storage->insert(
            'users',
            ['username' => $username],
            [
                'username' => $username,
                'password' => $encodedPassword,
                'assignments' => $assignments,
            ]
        );

        return $user;
    }

    protected function requestJson(string $method, string $uri, ?string $json, array $headers = []): Response
    {
        $headers = array_merge(['CONTENT_TYPE' => 'application/json'], $headers);

        $this->getClient()->xmlHttpRequest($method, $uri, [], [], $headers, $json);

        return $this->getClient()->getResponse();
    }

    protected function getJson(string $uri, array $headers = []): Response
    {
        return $this->requestJson('GET', $uri, null, $headers);
    }

    protected function postJson(string $uri, string $json, array $headers = [])
    {
        return $this->requestJson('POST', $uri, $json, $headers);
    }

    protected function putJson(string $uri, string $json, array $headers = [])
    {
        return $this->requestJson('PUT', $uri, $json, $headers);
    }

    protected function assertApiProblemResponse(int $statusCode, string $title, string $detail = null, string $type = 'about:blank'): void
    {
        $this->assertTrue(
            $this->getClient()->getResponse()->headers->contains('Content-Type', 'application/problem+json'),
            'The "Content-Type" header should be "application/problem+json"'
        );

        $this->assertJson($this->getClient()->getResponse()->getContent(), 'The content should be a valid JSON string');

        $response = json_decode($this->getClient()->getResponse()->getContent(), true);

        $contentShouldBe = [
            'status' => $statusCode,
            'type'   => $type,
            'title'  => $title
        ];

        if (null !== $detail) {
            $contentShouldBe['detail'] = $detail;
        } else {
            $this->assertArrayHasKey('detail', $response, '"detail" key should exist');
        }

        $this->assertArraySubset($contentShouldBe, $response, true, 'The content of error response is not as it is expected');
    }
}