<?php

namespace App\Tests\Controller;

use App\Model\LineItem;
use App\ODM\StorageInterface;
use App\Tests\BaseWebTestCase;

class AssignmentControllerTest extends BaseWebTestCase
{
    private const ENDPOINT_GET_ASSIGNMENTS = '/api/v1/assignments/';

    public function getUrlsForMethodNotAllowed()
    {
        return [
            ['PUT', self::ENDPOINT_GET_ASSIGNMENTS],
        ];
    }

    public function testGetAssignmentsIsSecured()
    {
        $this->getJson(self::ENDPOINT_GET_ASSIGNMENTS);

        $this->assertEquals(401, $this->getClient()->getResponse()->getStatusCode());

        $this->assertApiProblemResponse(401, 'Unauthorized', 'Full authentication is required to access this resource.');
    }

    public function testGetAssignmentsReturnsEmptyArrayForUserNotHavingAssignments()
    {
        $user = $this->addUser('user_1', 'abc1');

        $this->logInAs($user);

        $this->getJson(self::ENDPOINT_GET_ASSIGNMENTS);

        $this->assertEquals(200, $this->getClient()->getResponse()->getStatusCode());
        $this->assertTrue(
            $this->getClient()->getResponse()->headers->contains('Content-Type', 'application/json'),
            'The "Content-Type" header should be "application/json"'
        );
        $this->assertJsonStringEqualsJsonString('{"assignments":[]}', $this->getClient()->getResponse()->getContent());
    }

    protected function addLineItem(): LineItem
    {
        $storage = self::$container->get(StorageInterface::class);

        $lineItem = new LineItem('http://line_item_tao.uri', 'label', 'infra_id', new \DateTimeImmutable('now'), new \DateTimeImmutable('+10 days'));

        $storage->insert(
            'line_items',
            ['taoUri' => $lineItem->getTaoUri()],
            [
                'taoUri' => $lineItem->getTaoUri(),
                'label' => $lineItem->getLabel(),
                'infrastructureId' => $lineItem->getInfrastructureId(),
                'startDateTime' => $lineItem->getStartDateTime()->format('Y-m-d H:i:s'),
                'endDateTime' => $lineItem->getEndDateTime()->format('Y-m-d H:i:s'),
            ]
        );

        return $lineItem;
    }

    public function testGetAssignmentsReturnsProperAssignments()
    {
        $lineItem = $this->addLineItem();

        $user = $this->addUser(
            'user_1',
            'pwd',
            [
                ['id' => 'adfadf1121', 'lineItemTaoUri' => $lineItem->getTaoUri(), 'state' => 'started'],
                ['id' => 'fgfdg344er', 'lineItemTaoUri' => $lineItem->getTaoUri(), 'state' => 'started'],
                ['id' => 'adfaf234555656', 'lineItemTaoUri' => $lineItem->getTaoUri(), 'state' => 'cancelled']
            ]
        );

        $this->logInAs($user);

        $this->getJson(self::ENDPOINT_GET_ASSIGNMENTS);

        $this->assertSame(200, $this->getClient()->getResponse()->getStatusCode());

        $expectedResponse = [
            'assignments' =>
                [
                    0 =>
                        [
                            'id' => 'adfadf1121',
                            'username' => $user->getUsername(),
                            'state' => 'started',
                            'lineItem' => [
                                'uri' => $lineItem->getTaoUri(),
                                'label' => $lineItem->getLabel(),
                                'startDateTime' => $lineItem->getStartDateTime()->getTimestamp(),
                                'endDateTime' => $lineItem->getEndDateTime()->getTimestamp(),
                                'infrastructure' => $lineItem->getInfrastructureId(),
                            ],
                        ],
                    1 =>
                        [
                            'id' => 'fgfdg344er',
                            'username' => $user->getUsername(),
                            'state' => 'started',
                            'lineItem' =>[
                                'uri' => $lineItem->getTaoUri(),
                                'label' => $lineItem->getLabel(),
                                'startDateTime' => $lineItem->getStartDateTime()->getTimestamp(),
                                'endDateTime' => $lineItem->getEndDateTime()->getTimestamp(),
                                'infrastructure' => $lineItem->getInfrastructureId(),
                            ],
                        ],
                ],
        ];

        $this->assertEquals($expectedResponse, json_decode($this->getClient()->getResponse()->getContent(), true));
    }
}