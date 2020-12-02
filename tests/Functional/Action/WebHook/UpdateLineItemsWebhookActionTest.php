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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\WebHook;

use Doctrine\Common\Cache\CacheProvider;
use JsonException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLineItemsWebhookActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    /** @var CacheProvider */
    private $resultCacheImplementation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $ormConfiguration = $this->getEntityManager()->getConfiguration();
        $resultCacheImplementation = $ormConfiguration->getResultCacheImpl();

        if (!$resultCacheImplementation instanceof CacheProvider) {
            throw new DoctrineResultCacheImplementationNotFoundException(
                'Doctrine result cache implementation is not configured.'
            );
        }

        $this->resultCacheImplementation = $resultCacheImplementation;
        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler();
    }

    /**
     * @dataProvider provideWrongRequestBodies
     *
     * @throws JsonException
     */
    public function testItThrowsBadRequestIfRequestIsInvalid(
        ?array $requestBody,
        string $expectedMessage
    ): void {
        if (null !== $requestBody) {
            $requestBody = (string)json_encode($requestBody);
        }

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [],
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

    public function testItAcceptLineItemsToBeUpdated(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [],
            (string)json_encode($this->getSuccessRequestBody())
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        /** @var LineItemRepository $lineItemRepository */
        $lineItemRepository = $this->getRepository(LineItem::class);

        /** @var LineItem $lineItem */
        $lineItem = $lineItemRepository->findOneBy(
            [
                'slug' => 'lineItemSlug'
            ]
        );

        $lineItemCache = $this->resultCacheImplementation->fetch('line_item_1');

        self::assertEquals(
            'https://docker.localhost/ontologies/tao.rdf#RightOne',
            $lineItem->getUri()
        );

        self::assertEquals(
            'https://docker.localhost/ontologies/tao.rdf#RightOne',
            current($lineItemCache)[0]['uri_1']
        );

        self::assertSame(
            [
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'status' => 'ignored'
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'status' => 'error'
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232c',
                    'status' => 'ignored'
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232c',
                    'status' => 'accepted'
                ]
            ],
            $decodedResponse
        );
    }


    public function testItAcceptsRequestButIgnoreUnknownsEvents(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/web-hooks/update-line-items',
            [],
            [],
            [],
            (string)json_encode($this->getRequestBodyUnknownEvent())
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame(
            [
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'status' => 'ignored'
                ]
            ],
            $decodedResponse
        );
    }

    public function provideWrongRequestBodies(): array
    {
        $missingDeliveryUri = 'Invalid Request Body: [events][0][eventData][deliveryURI] -> This field is missing.';
        $invalidAliasTypeMessage = 'Invalid Request Body: [events][0][eventName] -> This field is missing.'
            . ' [events][0][eventData][alias] -> This value should be of type string.';

        return [
            'empty' => [
                'requestBody' => null,
                'expectedMessage' => 'Invalid JSON request body received. Error: Syntax error.',
            ],
            'emptyObject' => [
                'requestBody' => [],
                'expectedMessage' => 'Invalid Request Body: [events] -> This field is missing.',
            ],
            'IncompleteEventName' => [
                'requestBody' => [
                    'source' => 'https://someinstance.taocloud.org/',
                    'events' => [
                        [
                            'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                            'triggeredTimestamp' => 1565602371,
                            'eventData' => [
                                'alias' => 'qti-interactions-delivery',
                                'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => 'Invalid Request Body: [events][0][eventName] -> This field is missing.',
            ],
            'IncompleteEventId' => [
                'requestBody' => [
                    'source' => 'https://someinstance.taocloud.org/',
                    'events' => [
                        [
                            'eventName' => 'RemoteDeliveryPublicationFinishesssd',
                            'triggeredTimestamp' => 1565602371,
                            'eventData' => [
                                'alias' => 'qti-interactions-delivery',
                                'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => 'Invalid Request Body: [events][0][eventId] -> This field is missing.',
            ],
            'IncompleteEventTriggeredTimeStamp' => [
                'requestBody' => [
                    'source' => 'https://someinstance.taocloud.org/',
                    'events' => [
                        [
                            'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                            'eventName' => 'RemoteDeliveryPublicationFinishesssd',
                            'eventData' => [
                                'alias' => 'qti-interactions-delivery',
                                'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => 'Invalid Request Body: [events][0][triggeredTimestamp] -> This field is missing.',
            ],
            'IncompleteEventDeliveryUri' => [
                'requestBody' => [
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
                ],
                'expectedMessage' => $missingDeliveryUri,
            ],
            'WrongAliasType' => [
                'requestBody' => [
                    'source' => 'https://someinstance.taocloud.org/',
                    'events' => [
                        [
                            'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                            'triggeredTimestamp' => 1565602371,
                            'eventData' => [
                                'alias' => 123,
                                'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => $invalidAliasTypeMessage,
            ],
        ];
    }

    private function getSuccessRequestBody(): array
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
                        'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232f',
                    'eventName' => 'RemoteDeliveryPublicationFinished',
                    'triggeredTimestamp' => 1565602371,
                    'eventData' => [
                        'alias' => 'wrong-alias',
                        'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232c',
                    'eventName' => 'RemoteDeliveryPublicationFinished',
                    'triggeredTimestamp' => 1565602380,
                    'eventData' => [
                        'alias' => 'lineItemSlug',
                        'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
                [
                    'eventId' => '52a3de8dd0f270fd193f9f4bff05232c',
                    'eventName' => 'RemoteDeliveryPublicationFinished',
                    'triggeredTimestamp' => 1565602390,
                    'eventData' => [
                        'alias' => 'lineItemSlug',
                        'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#RightOne',
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
                        'deliveryURI' => 'https://docker.localhost/ontologies/tao.rdf#FFF',
                    ],
                ],
            ]
        ];
    }
}
