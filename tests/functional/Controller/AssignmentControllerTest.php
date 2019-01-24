<?php

namespace App\Tests\Functional\Controller;

use App\Model\Assignment;
use App\Model\User;
use App\Storage\InMemoryStorage;
use App\Storage\StorageInterface;
use App\Tests\Functional\AuthenticationTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Client;

class AssignmentControllerTest extends WebTestCase
{
    use AuthenticationTrait;

    /**
     * @var Client
     */
    private $client = null;

    private const ENDPOINT = '/api/v1/assignments/';

    public function setUp()
    {
        $this->client = static::createClient();
    }

    public function testItRequiresAuthentication()
    {
        $this->client->request('GET', self::ENDPOINT);

        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
    }

    public function testItReturnsEmptyArrayForUserNotHavingAssignments()
    {
        $this->logIn($this->client);

        $this->client->request('GET', self::ENDPOINT);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals('{"assignments":[]}', $response->getContent());
    }

    public function testItReturnsAssignments()
    {
        $storage = new InMemoryStorage();
        $storage->insert('line_items', ['taoUri' => 'http://line_item_1_tao.uri'], [
            'title' => 'title',
            'taoUri' => 'http://line_item_1_tao.uri',
            'infrastructureId' => 'infra_id',
            'startDateTime' => '',
            'endDateTime' => '',
        ]);
        $this->client->getContainer()->set(StorageInterface::class, $storage);

        $user = new User('login', 'encoded_password', 'salt', [
            new Assignment('http://line_item_1_tao.uri'),
            new Assignment('http://line_item_1_tao.uri'),
        ]);

        $this->logIn($this->client, $user);

        $this->client->request('GET', self::ENDPOINT);

        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $expectedResponse = array (
            'assignments' =>
                array (
                    0 =>
                        array (
                            'id' => 1,
                            'username' => 'login',
                            'lineItem' =>
                                array (
                                    'uri' => 'http://line_item_1_tao.uri',
                                    'login' => 'login',
                                    'name' => 'http://line_item_1_tao.uri',
                                    'startDateTime' => '',
                                    'endDateTime' => '',
                                    'infrastructure' => 'infra_id',
                                ),
                        ),
                    1 =>
                        array (
                            'id' => 2,
                            'username' => 'login',
                            'lineItem' =>
                                array (
                                    'uri' => 'http://line_item_1_tao.uri',
                                    'login' => 'login',
                                    'name' => 'http://line_item_1_tao.uri',
                                    'startDateTime' => '',
                                    'endDateTime' => '',
                                    'infrastructure' => 'infra_id',
                                ),
                        ),
                ),
        );

        $this->assertEquals($expectedResponse, json_decode($response->getContent(), true));
    }
}