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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Functional\Action\LineItem;

use Carbon\Carbon;
use DateTimeImmutable;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListLineItemsActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    private KernelBrowser $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . 'testApiKey']);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('100LineItems.yml');
    }

    public function testItThrowsUnauthorizedHttpExceptionIfRequestApiKeyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/line-items',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid'],
            '{}'
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('API key authentication failure.', $decodedResponse['error']['message']);
    }

    /**
     * @dataProvider provideInvalidParameters
     */
    public function testItThrowsInvalidArgumentExceptionForInvalidParameters(
        string $field,
        string $value,
        string $message
    ): void {
        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/line-items',
            [
                $field => $value
            ],
            [],
            [],
            null
        );

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame($message, $decodedResponse['error']['message']);
    }

    /**
     * @dataProvider provideValidParameters
     */
    public function testItReturnsValidResponseForMultipleParameters(
        array $parameters,
        int $expectedSize,
        ?int $nextCursor
    ): void {
        $this->kernelBrowser->request(
            Request::METHOD_GET,
            '/api/v1/line-items',
            $parameters,
            [],
            [],
            null
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertCount($expectedSize, $decodedResponse['data']);
        self::assertSame($nextCursor, $decodedResponse['metadata']['pagination']['nextCursor']);
    }

    public function provideInvalidParameters(): array
    {
        return [
            'startTimeStampIsZero' => [
                'field' => 'startAt',
                'value' => '0',
                'message' => 'Invalid timestamp for startAt: 0',
            ],
            'startTimeStampIsNegative' => [
                'field' => 'startAt',
                'value' => '-1',
                'message' => 'Invalid timestamp for startAt: -1',
            ],
            'startTimeStampIsString' => [
                'field' => 'startAt',
                'value' => 'abc',
                'message' => 'Invalid timestamp for startAt: abc',
            ],
            'endTimeStampIsZero' => [
                'field' => 'endAt',
                'value' => '0',
                'message' => 'Invalid timestamp for endAt: 0',
            ],
            'endTimeStampIsNegative' => [
                'field' => 'endAt',
                'value' => '-1',
                'message' => 'Invalid timestamp for endAt: -1',
            ],
            'endTimeStampIsString' => [
                'field' => 'endAt',
                'value' => 'abc',
                'message' => 'Invalid timestamp for endAt: abc',
            ],
            'limitIsHigherThan100' => [
                'field' => 'limit',
                'value' => '110',
                'message' => 'Max limit is 100',
            ],
        ];
    }

    /**
     * * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function provideValidParameters(): array
    {
        return [
            'noParameters' => [
                'parameters' => [],
                'expectedSize' => 100,
                'nextCursor' => 100
            ],
            'withDefinedLimit' => [
                'parameters' => [
                    'limit' => 10,
                ],
                'expectedSize' => 10,
                'nextCursor' => 10
            ],
            'withDefinedCursor' => [
                'parameters' => [
                    'limit' => 10,
                    'cursor' => 10,
                ],
                'expectedSize' => 10,
                'nextCursor' => 20
            ],
            'filterId' => [
                'parameters' => [
                    'id' => '1',
                ],
                'expectedSize' => 1,
                'nextCursor' => null
            ],
            'filterSingleSlug' => [
                'parameters' => [
                    'slug' => 'slug-30',
                ],
                'expectedSize' => 1,
                'nextCursor' => null
            ],
            'filterMultipleSlugs' => [
                'parameters' => [
                    'slug' => ['slug-30', 'slug-40', 'slug-50', 'slug-1000'],
                ],
                'expectedSize' => 3,
                'nextCursor' => null
            ],
            'filterSingleLabel' => [
                'parameters' => [
                    'label' => 'label-30',
                ],
                'expectedSize' => 1,
                'nextCursor' => null
            ],
            'filterMultipleByLabels' => [
                'parameters' => [
                    'label' => ['label-30', 'label-40', 'label-50', 'label-1000'],
                ],
                'expectedSize' => 3,
                'nextCursor' => null
            ],
            'filterSingleUri' => [
                'parameters' => [
                    'uri' => 'https://test.taocloud.fr/__n/30',
                ],
                'size' => 1,
                'nextCursor' => null
            ],
            'filterMultipleUris' => [
                'parameters' => [
                    'uri' => [
                        'https://test.taocloud.fr/__n/30',
                        'https://test.taocloud.fr/__n/40',
                        'https://test.taocloud.fr/__n/50',
                        'https://test.taocloud.fr/__n/1000'
                    ],
                ],
                'size' => 3,
                'nextCursor' => null
            ],
            'filterStartAt' => [
                'parameters' => [
                    'startAt' => (new DateTimeImmutable('2021-07-01 00:00:00'))->getTimestamp(),
                ],
                'expectedSize' => 50,
                'nextCursor' => null
            ],
            'filterEndAt' => [
                'parameters' => [
                    'endAt' => (new DateTimeImmutable('2021-07-20 00:00:00'))->getTimestamp(),
                ],
                'expectedSize' => 50,
                'nextCursor' => null
            ],
            'filterCombinedParameters' => [
                'parameters' => [
                    'slug' => ['slug-1', 'slug-60', 'slug-61', 'slug-62'],
                    'label' => ['label-1', 'label-60', 'label-61', 'label-62', 'label-63'],
                    'uri' => [
                        'https://test.taocloud.fr/__n/1',
                        'https://test.taocloud.fr/__n/60',
                        'https://test.taocloud.fr/__n/62',
                        'https://test.taocloud.fr/__n/63'
                    ],
                    'startAt' => (new DateTimeImmutable('2021-07-01 00:00:00'))->getTimestamp(),
                    'endAt' => (new DateTimeImmutable('2021-07-20 00:00:00'))->getTimestamp(),
                ],
                'expectedSize' => 2,
                'nextCursor' => null
            ],
        ];
    }
}
