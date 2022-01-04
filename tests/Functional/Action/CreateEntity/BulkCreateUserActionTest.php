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

namespace OAT\SimpleRoster\Tests\Functional\Action\CreateEntity;

use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BulkCreateUserActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    private KernelBrowser $kernelBrowser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient([], ['HTTP_AUTHORIZATION' => 'Bearer ' . 'testApiKey']);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('lineItemsAndLtiInstances.yml');

        $this->setUpTestLogHandler();
    }

    public function testItThrowsUnauthorizedHttpExceptionIfRequestApiKeyIsInvalid(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk-create-users',
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
    public function testItCreateBulkUserWithCorrectData(string $body, array $response): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk-create-users',
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
     * @dataProvider provideNonExistingSlugBody
     */
    public function testItThrowErrorForNonExistSlugData(string $body, string $message): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk-create-users',
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

    /**
     * @dataProvider provideInvalidInformedFieldsBody
     */
    public function testItShouldValidateCreateBulkUserInformedFields(string $body, string $message): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/bulk-create-users',
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
                    'lineItemSlug' => 'slug-qqyw',
                    'userPrefixes' => ['QA','LQA'],
                    'quantity' => 4,
                    'groupIdPrefix' => 'TestCollege',
                ]),
                'response' => [
                    'message' => "8 users created for line item slug-qqyw for user prefix QA,LQA",
                    'nonExistingLineItems' => [],
                ]
            ]
        ];
    }

    public function provideNonExistingSlugBody(): array
    {
        return [
            'withoutExistingSlug' => [
                'request' => json_encode([
                    'lineItemSlug' => 'my-slug',
                    'userPrefixes' => ['QA','LQA'],
                    'quantity' => 4,
                    'groupIdPrefix' => 'TestCollege',
                ]),
                'message' => 'my-slug Line item slug(s) not exist in the system'
            ]
        ];
    }

    public function provideInvalidInformedFieldsBody(): array
    {
        return [
            'emptyBody' => [
                'request' => json_encode([]),
                'message' => 'Invalid Request Body: '
                    . '[lineItemSlug] -> This field is missing. '
                    . '[userPrefixes] -> This field is missing.'
            ],
            'missingLineItemSlug' => [
                'request' => json_encode([
                    'userPrefixes' => ["OAT", "QA"],
                    'groupIdPrefix' => 'fdfdf',
                    'quantity' => 4,
                ]),
                'message' => 'Invalid Request Body: [lineItemSlug] -> This field is missing.'
            ],
            'missingUserPrefixes' => [
                'request' => json_encode([
                    'lineItemSlug' => 'my-slug',
                    'groupIdPrefix' => 'fdfdf',
                    'quantity' => 4,
                ]),
                'message' => 'Invalid Request Body: [userPrefixes] -> This field is missing.'
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
