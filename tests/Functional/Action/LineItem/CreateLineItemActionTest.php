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

use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateLineItemActionTest extends WebTestCase
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

        $this->setUpTestLogHandler();
    }

    public function testItThrowsUnauthorizedHttpExceptionIfRequestApiKeyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
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
     * @dataProvider provideValidBody
     */
    public function testItCreateLineItemWithCorrectData(string $body, array $response): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/line-items',
            [],
            [],
            [],
            $body
        );

        $this->assertArrayValues(
            json_decode($this->kernelBrowser->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
            $response,
        );
    }

    /**
     * @dataProvider provideInvalidBody
     */
    public function testItShouldValidateInformedFields(string $body, string $message): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/line-items',
            [],
            [],
            [],
            $body
        );

        $decodedResponse = json_decode($this->kernelBrowser->getResponse()->getContent(), true);
        self::assertSame(
            $message,
            $decodedResponse['error']['message']
        );
    }

    public function provideValidBody(): array
    {
        return [
            'withAllFields' => [
                'request' => json_encode([
                    'slug' => 'my-slug',
                    'uri' => 'my-uri',
                    'startDateTime' => '2021-01-01T00:00:00+0000',
                    'endDateTime' => '2021-01-31T00:00:00+0000',
                ]),
                'response' => [
                    'slug' => 'my-slug',
                    'uri' => 'my-uri',
                    'label' => 'my-slug',
                    'isActive' => true,
                    'startDateTime' => 1609459200,
                    'endDateTime' => 1612051200,
                    'maxAttempts' => 0,
                ],
            ],
            'withExistingSlug' => [
                'request' => json_encode([
                    'slug' => 'slug-1',
                    'uri' => 'my-uri',
                    'startDateTime' => '2021-01-01T00:00:00+0000',
                    'endDateTime' => '2021-01-31T00:00:00+0000',
                ]),
                'response' => [
                    'id' => 1,
                    'uri' => "https://test.taocloud.fr/__n/1",
                    'label' => "label-1",
                    'slug' => "slug-1",
                    'isActive' => true,
                    'startDateTime' => "",
                    'endDateTime' => "",
                    'maxAttempts' => 0,
                ],
            ],
            'withoutAllDates' => [
                'request' => json_encode([
                    'slug' => 'my-slug',
                    'uri' => 'my-uri',
                ]),
                'response' => [
                    'slug' => 'my-slug',
                    'uri' => 'my-uri',
                    'label' => 'my-slug',
                    'isActive' => true,
                    'startDateTime' => '',
                    'endDateTime' => '',
                    'maxAttempts' => 0,
                ],
            ],
        ];
    }

    public function provideInvalidBody(): array
    {
        return [
            'emptyBody' => [
                'request' => json_encode([]),
                'message' => 'Invalid Request Body: [slug] -> This field is missing. [uri] -> This field is missing.',
            ],
            'missingSlug' => [
                'request' => json_encode([
                    'uri' => 'my-uri',
                ]),
                'message' => 'Invalid Request Body: [slug] -> This field is missing.',
            ],
            'missingUri' => [
                'request' => json_encode([
                    'slug' => 'my-slug',
                ]),
                'message' => 'Invalid Request Body: [uri] -> This field is missing.',
            ],
            'withInvalidDates' => [
                'request' => json_encode([
                    'slug' => 'my-slug',
                    'uri' => 'my-uri',
                    'startDateTime' => '2022-01-01',
                    'endDateTime' => '2022-10-30',
                ]),
                'message' => 'Invalid JSON request body received. Error: Invalid Date Time format.',
            ],
        ];
    }

    private function assertArrayValues(array $response, array $keyValues): void
    {
        foreach ($keyValues as $key => $value) {
            self::assertSame($response[$key], $value);
        }
    }
}
