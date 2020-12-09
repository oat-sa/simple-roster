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

namespace OAT\SimpleRoster\Tests\Functional\Action\Security;

use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginActionTest extends WebTestCase
{
    use DatabaseTestingTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        $this->kernelBrowser = self::createClient();

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        parent::setUp();
    }

    public function testItFailsWithWrongCredentials(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'invalid', 'password' => 'invalid'], JSON_THROW_ON_ERROR, 512)
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Invalid credentials.', $decodedResponse['error']);
    }

    public function testItLogsInProperlyTheUser(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'], JSON_THROW_ON_ERROR, 512)
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertArrayHasKey('accessToken', json_decode($this->kernelBrowser->getResponse()->getContent(), true));
    }

    public function testItLogsInProperlyTheUserAndReturnsRefreshToken(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'], JSON_THROW_ON_ERROR, 512)
        );

        self::assertSame(Response::HTTP_OK, $this->kernelBrowser->getResponse()->getStatusCode());

        self::assertArrayHasKey('accessToken', json_decode($this->kernelBrowser->getResponse()->getContent(), true));

        self::assertArrayHasKey('refreshToken', json_decode($this->kernelBrowser->getResponse()->getContent(), true));
    }
}
