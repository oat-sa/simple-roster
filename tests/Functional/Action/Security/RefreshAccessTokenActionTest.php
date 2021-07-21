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

namespace OAT\SimpleRoster\Tests\Functional\Action\Security;

use Carbon\Carbon;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

class RefreshAccessTokenActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;

    private UserRepository $userRepository;
    private JwtTokenGenerator $tokenGenerator;
    private CacheItemPoolInterface $tokenCache;
    private JwtTokenCacheIdGenerator $tokenCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $this->setUpDatabase();

        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->userRepository = self::$container->get(UserRepository::class);
        $this->tokenGenerator = self::$container->get(JwtTokenGenerator::class);
        $this->tokenCache = self::$container->get('app.jwt_cache.adapter');
        $this->tokenCacheIdGenerator = self::$container->get(JwtTokenCacheIdGenerator::class);
    }

    public function testIfAccessTokenCanBeRefreshed(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->authenticateAs($user);

        $initialAccessToken = $authenticationResponse->getAccessToken();

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => (string)$authenticationResponse->getRefreshToken()], JSON_THROW_ON_ERROR)
        );

        $this->assertApiStatusCode(Response::HTTP_OK);

        $decodedResponse = $this->getDecodedJsonApiResponse();
        self::assertArrayHasKey('accessToken', $decodedResponse);
        self::assertNotSame($initialAccessToken, $decodedResponse['accessToken']);
    }

    public function testItReturnsExceptionIfRefreshTokenIsMissingFromRequest(): void
    {
        $this->kernelBrowser->request(Request::METHOD_POST, '/api/v1/auth/refresh-token');

        $this->assertApiStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertApiErrorResponseMessage("Missing 'refreshToken' in request body.");
    }

    public function testItReturnsExceptionIfInvalidRefreshTokenReceived(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => 'invalidToken'], JSON_THROW_ON_ERROR)
        );

        $this->assertApiStatusCode(Response::HTTP_BAD_REQUEST);
        $this->assertApiErrorResponseMessage('Invalid token.');
    }

    public function testItReturnsExceptionIfUserCannotBeFound(): void
    {
        $tokenForNonExistingUser = $this->tokenGenerator->create(
            new User(new UuidV6(), 'notExisting', 'testPassword'),
            Request::create('/test'),
            'refreshToken',
            3600
        );

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => (string)$tokenForNonExistingUser], JSON_THROW_ON_ERROR)
        );

        $this->assertApiStatusCode(Response::HTTP_FORBIDDEN);
        $this->assertApiErrorResponseMessage('Invalid token.');
    }

    public function testItReturnsExceptionIfRefreshTokenIsExpired(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');
        $authenticationResponse = $this->authenticateAs($user);

        Carbon::setTestNow('+2 years');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => (string)$authenticationResponse->getRefreshToken()], JSON_THROW_ON_ERROR)
        );

        Carbon::setTestNow();

        $this->assertApiStatusCode(Response::HTTP_FORBIDDEN);
        $this->assertApiErrorResponseMessage('Expired token.');
    }

    public function testItReturnsExceptionIfRefreshTokenCannotBeFoundInCache(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');
        $authenticationResponse = $this->authenticateAs($user);

        $cacheId = $this->tokenCacheIdGenerator->generate($authenticationResponse->getRefreshToken());
        $this->tokenCache->deleteItem($cacheId);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => (string)$authenticationResponse->getRefreshToken()], JSON_THROW_ON_ERROR)
        );

        $this->assertApiStatusCode(Response::HTTP_FORBIDDEN);
        $this->assertApiErrorResponseMessage('Expired token.');
    }
}
