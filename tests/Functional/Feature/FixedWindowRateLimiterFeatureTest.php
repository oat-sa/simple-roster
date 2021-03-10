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

namespace OAT\SimpleRoster\Tests\Functional\Feature;

use Doctrine\Common\Cache\CacheProvider;
use JsonException;
use Monolog\Logger;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Exception\DoctrineResultCacheImplementationNotFoundException;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FixedWindowRateLimiterFeatureTest extends WebTestCase
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

        $this->setUpTestLogHandler('security');
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItAcceptsRequestsAfterItervalOfTwoSeconds(): void
    {
        $_ENV['RATE_LIMITER_FIXED_WINDOW_ROUTES'] = 'updateLineItems';
        $_ENV['RATE_LIMITER_FIXED_WINDOW_LIMIT'] = 2;
        $_ENV['RATE_LIMITER_FIXED_WINDOW_INTERVAL'] = '2 seconds';

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->resetKernel();

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertStringContainsString(
            "Rate Limit Exceeded. Please retry after",
            (string)$this->kernelBrowser->getResponse()->getContent(),
        );
        $this->assertHasLogRecord(
            [
                'message' => 'The client with ip: 127.0.0.1, exceeded the limit of requests.',
                'context' => [
                    'routes' => ['updateLineItems'],
                    'limit' => 2
                ],
            ],
            Logger::WARNING
        );

        sleep(2);

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItAcceptsRequestsIfThereIsNoRouteConfigured(): void
    {
        $_ENV['RATE_LIMITER_FIXED_WINDOW_ROUTES'] = '';
        $_ENV['RATE_LIMITER_FIXED_WINDOW_LIMIT'] = 2;
        $_ENV['RATE_LIMITER_FIXED_WINDOW_INTERVAL'] = '2 second';

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItAcceptsRequestsWithDifferentRouteConfigured(): void
    {
        $_ENV['RATE_LIMITER_FIXED_WINDOW_ROUTES'] = 'healthCheck';
        $_ENV['RATE_LIMITER_FIXED_WINDOW_LIMIT'] = 2;
        $_ENV['RATE_LIMITER_FIXED_WINDOW_INTERVAL'] = '2 second';

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItBlocksRequestAfter2RequestsInItervalOfTwoSeconds(): void
    {
        $_ENV['RATE_LIMITER_FIXED_WINDOW_ROUTES'] = 'updateLineItems';
        $_ENV['RATE_LIMITER_FIXED_WINDOW_LIMIT'] = 1;
        $_ENV['RATE_LIMITER_FIXED_WINDOW_INTERVAL'] = '2 second';

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->resetKernel();

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertStringContainsString(
            "Rate Limit Exceeded. Please retry after",
            (string)$this->kernelBrowser->getResponse()->getContent(),
        );
        $this->assertHasLogRecord(
            [
                'message' => 'The client with ip: 127.0.0.1, exceeded the limit of requests.',
                'context' => [
                    'routes' => ['updateLineItems'],
                    'limit' => 1
                ],
            ],
            Logger::WARNING
        );
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function testItBlocksRequestWithMultipleRoutesAfter2RequestsInItervalOfTwoSeconds(): void
    {
        $_ENV['RATE_LIMITER_FIXED_WINDOW_ROUTES'] = 'updateLineItems,healthCheck';
        $_ENV['RATE_LIMITER_FIXED_WINDOW_LIMIT'] = 2;
        $_ENV['RATE_LIMITER_FIXED_WINDOW_INTERVAL'] = '2 second';

        $this->executeUpdateLineItemsRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->executeHealthCheckRequest();

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $this->resetKernel();

        $this->executeHealthCheckRequest();

        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $this->kernelBrowser->getResponse()->getStatusCode());
        self::assertStringContainsString(
            "Rate Limit Exceeded. Please retry after",
            (string)$this->kernelBrowser->getResponse()->getContent(),
        );
        $this->assertHasLogRecord(
            [
                'message' => 'The client with ip: 127.0.0.1, exceeded the limit of requests.',
                'context' => [
                    'routes' => ['updateLineItems', 'healthCheck'],
                    'limit' => 2
                ],
            ],
            Logger::WARNING
        );
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
                ]
            ],
        ];
    }

    private function executeUpdateLineItemsRequest(): void
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
    }

    private function executeHealthCheckRequest(): void
    {
        $this->kernelBrowser->request(Request::METHOD_GET, '/api/v1');
    }

    private function resetKernel(): void
    {
        self::ensureKernelShutdown();
        $this->kernelBrowser = self::createClient();
        $this->setUpTestLogHandler('security');
    }
}
