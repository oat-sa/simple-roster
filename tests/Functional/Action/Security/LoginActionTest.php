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

use Lcobucci\JWT\Parser;
use Monolog\Logger;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LoginActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->userRepository = self::$container->get(UserRepository::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler('security');
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

        $this->assertApiStatusCode(Response::HTTP_UNAUTHORIZED);
        $this->assertApiErrorResponse('Invalid credentials.');
    }

    public function testSuccessfulAuthentication(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->authenticateAs($user);

        $this->assertApiStatusCode(Response::HTTP_OK);

        $decodedResponse = $this->getDecodedJsonApiResponse();

        self::assertArrayHasKey('accessToken', $decodedResponse);
        self::assertArrayHasKey('refreshToken', $decodedResponse);

        $jwtParser = new Parser();

        try {
            $accessToken = $jwtParser->parse($decodedResponse['accessToken']);
            $refreshToken = $jwtParser->parse($decodedResponse['refreshToken']);

            $this->assertHasLogRecord([
                'message' => sprintf(
                    "Token 'accessToken' with id '%s' has been generated for user 'user1'.",
                    $accessToken->getClaim('jti')
                ),
            ], Logger::INFO);

            $this->assertHasLogRecord([
                'message' => sprintf(
                    "Token 'refreshToken' with id '%s' has been generated for user 'user1'.",
                    $refreshToken->getClaim('jti')
                ),
                'context' => [
                    'cacheId' => 'jwt.refreshToken.user1',
                    'cacheTtl' => 86400,
                ],
            ], Logger::INFO);
        } catch (Throwable $throwable) {
            self::fail('JWT token parsing error: ' . $throwable->getMessage());
        }
    }
}
