<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\WebHook;

use JsonException;
use Monolog\Logger;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLineItemsWebhookActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    private KernelBrowser $kernelBrowser;
    private CacheItemPoolInterface $resultCacheImplementation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $ormConfiguration = $this->getEntityManager()->getConfiguration();
        $resultCacheImplementation = $ormConfiguration->getResultCache();

        if (!$resultCacheImplementation instanceof CacheItemPoolInterface) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler();
    }

    public function testItReturns401IfNoCredentialsInformed(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [],
            ''
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    public function testItReturns401IfWrongCredentialsInformed(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [
                'PHP_AUTH_USER' => 'wrongUsername',
                'PHP_AUTH_PW' => 'wrongPassword',
            ],
            ''
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    /**
     * @dataProvider provideWrongRequestBodies
     *
     * @throws JsonException
     */
    public function testItThrowsBadRequestIfRequestIsInvalid(
        string $requestBody,
        string $expectedMessage
    ): void {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [
                'PHP_AUTH_USER' => 'testUsername',
                'PHP_AUTH_PW' => 'testPassword',
            ],
            $requestBody
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            $expectedMessage,
            $decodedResponse['error']['message']
        );
    }

    /**
     * @throws JsonException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testItAcceptLineItemsToBeUpdated(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [
                'PHP_AUTH_USER' => 'testUsername',
                'PHP_AUTH_PW' => 'testPassword',
            ],
            (string)json_encode($this->getSuccessRequestBody())
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        /** @var LineItemRepository $lineItemRepository */
        $lineItemRepository = $this->getRepository(LineItem::class);

        /** @var LineItem $lineItem */
        $lineItem = $lineItemRepository->findOneBy(['slug' => 'lineItemSlug']);

        $lineItemCache = $this->resultCacheImplementation->getItem('lineItem.1')->get();

        $this->assertHasLogRecord(
            [
                'message' => 'The line item was created',
                'context' => [
                    'slug' => 'wrong-alias',
                    'uri' => 'https://docker.localhost/ontologies/tao.rdf#FFF'
                ],
            ],
            Logger::INFO
        );

        $this->assertHasLogRecord(
            [
                'message' => 'The line item id 1 was updated',
                'context' => [
                    'oldUri' => 'http://lineitemuri.com',
                    'newUri' => 'https://docker.localhost/ontologies/tao.rdf#RightOne',
                ],
            ],
            Logger::INFO
        );

        self::assertSame('https://docker.localhost/ontologies/tao.rdf#RightOne', $lineItem->getUri());

        self::assertSame(
            'https://docker.localhost/ontologies/tao.rdf#RightOne',
            current($lineItemCache)[0]['uri_1']
        );

        self::assertSame(
            [
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'status' => 'ignored',
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'status' => 'accepted',
                ],
                [
                    'eventId' => 'lastDuplicatedEvent',
                    'status' => 'accepted',
                ],
                [
                    'eventId' => 'duplicated',
                    'status' => 'ignored',
                ],
            ],
            json_decode(
                $this->kernelBrowser->getResponse()->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function testItAcceptsRequestButIgnoreUnknownEvents(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [
                'PHP_AUTH_USER' => 'testUsername',
                'PHP_AUTH_PW' => 'testPassword',
            ],
            (string)json_encode($this->getRequestBodyUnknownEvent())
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertSame(
            [
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'status' => 'ignored',
                ],
            ],
            json_decode(
                $this->kernelBrowser->getResponse()->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function provideWrongRequestBodies(): array
    {
        $missingRemoteDeliveryId = 'Invalid Request Body: '
            . '[events][0][eventData][remoteDeliveryId] -> This field is missing.';

        $invalidAliasType = 'Invalid Request Body: [events][0][eventName] -> This field is missing.'
            . ' [events][0][eventData][alias] -> This value should be of type string.';

        $eventsEmpty = 'Invalid Request Body: [events] -> This collection should contain 1 element or more.';

        return [
            'empty' => [
                'requestBody' => '',
                'expectedMessage' => 'Invalid JSON request body received. Error: Syntax error.',
            ],
            'emptyObject' => [
                'requestBody' => json_encode([]),
                'expectedMessage' => 'Invalid Request Body: [events] -> This field is missing.',
            ],
            'IncompleteEventName' =>
                [
                    'requestBody' => json_encode(
                        [
                            'source' => 'https://someinstance.taocloud.org/',
                            'events' => [
                                [
                                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                                    'triggeredTimestamp' => 1565602371,
                                    'eventData' => [
                                        'alias' => 'qti-interactions-delivery',
                                        'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                                    ],
                                ],
                            ],
                        ]
                    ),
                    'expectedMessage' => 'Invalid Request Body: [events][0][eventName] -> This field is missing.',
                ]
            ,
            'IncompleteEventId' => [
                'requestBody' => json_encode(
                    [
                        'source' => 'https://someinstance.taocloud.org/',
                        'events' => [
                            [
                                'eventName' => 'RemoteDeliveryPublicationFinishesssd',
                                'triggeredTimestamp' => 1565602371,
                                'eventData' => [
                                    'alias' => 'qti-interactions-delivery',
                                    'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                                ],
                            ],
                        ],
                    ]
                ),
                'expectedMessage' => 'Invalid Request Body: [events][0][eventId] -> This field is missing.',
            ],
            'IncompleteEventTriggeredTimeStamp' => [
                'requestBody' => json_encode(
                    [
                        'source' => 'https://someinstance.taocloud.org/',
                        'events' => [
                            [
                                'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                                'eventName' => 'RemoteDeliveryPublicationFinishesssd',
                                'eventData' => [
                                    'alias' => 'qti-interactions-delivery',
                                    'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                                ],
                            ],
                        ],
                    ]
                ),
                'expectedMessage' => 'Invalid Request Body: [events][0][triggeredTimestamp] -> This field is missing.',
            ],
            'IncompleteEventRemoteDeliveryId' => [
                'requestBody' => json_encode(
                    [
                        'source' => 'https://someinstance.taocloud.org/',
                        'events' => [
                            [
                                'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                                'eventName' => 'RemoteDeliveryPublicationFinishesssd',
                                'triggeredTimestamp' => 1565602371,
                                'eventData' => [
                                    'alias' => 'qti-interactions-delivery',
                                ],
                            ],
                        ],
                    ]
                ),
                'expectedMessage' => $missingRemoteDeliveryId,
            ],
            'WrongAliasType' => [
                'requestBody' => json_encode(
                    [
                        'source' => 'https://someinstance.taocloud.org/',
                        'events' => [
                            [
                                'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                                'triggeredTimestamp' => 1565602371,
                                'eventData' => [
                                    'alias' => 123,
                                    'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                                ],
                            ],
                        ],
                    ]
                ),
                'expectedMessage' => $invalidAliasType,
            ],
            'EventsEmpty' => [
                'requestBody' => json_encode(
                    [
                        'source' => 'https://someinstance.taocloud.org/',
                        'events' => [],
                    ]
                ),
                'expectedMessage' => $eventsEmpty,
            ],
            'EventsAsString' => [
                'requestBody' => json_encode(
                    [
                        'source' => 'https://someinstance.taocloud.org/',
                        'events' => 'string',
                    ]
                ),
                'expectedMessage' => 'Invalid Request Body: [events] -> This value should be of type array.',
            ],
        ];
    }

    private function getSuccessRequestBody(): array
    {
        return [
            'source' => 'https://someinstance.taocloud.org/',
            'withExtraFields' => true,
            'events' => [
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'eventName' => 'WrongEvent',
                    'triggeredTimestamp' => 1565602371,
                    'eventData' => [
                        'alias' => 'qti-interactions-delivery',
                        'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                        'withExtraFields' => true,
                    ],
                    'withExtraFields' => true,
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'eventName' => 'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                    'triggeredTimestamp' => 1565602371,
                    'eventData' => [
                        'alias' => 'wrong-alias',
                        'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
                [
                    'eventId' => 'lastDuplicatedEvent',
                    'eventName' => 'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                    'triggeredTimestamp' => 1565602390,
                    'eventData' => [
                        'alias' => 'lineItemSlug',
                        'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#RightOne',
                    ],
                ],
                [
                    'eventId' => 'duplicated',
                    'eventName' => 'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent',
                    'triggeredTimestamp' => 1565602380,
                    'eventData' => [
                        'alias' => 'lineItemSlug',
                        'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
            ],
        ];
    }

    private function getRequestBodyUnknownEvent(): array
    {
        return [
            'source' => 'https://someinstance.taocloud.org/',
            'events' => [
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'eventName' => 'WrongEvent',
                    'triggeredTimestamp' => 1565602371,
                    'eventData' => [
                        'alias' => 'qti-interactions-delivery',
                        'remoteDeliveryId' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
            ],
        ];
    }
}
