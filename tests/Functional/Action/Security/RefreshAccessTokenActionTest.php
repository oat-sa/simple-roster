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
use Lcobucci\JWT\Parser;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\UserAuthenticatorTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshAccessTokenActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use UserAuthenticatorTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    /** @var UserRepository */
    private $userRepository;

    /** @var JwtTokenGenerator */
    private $tokenGenerator;

    /** @var CacheItemPoolInterface */
    private $tokenCache;

    /** @var JwtTokenCacheIdGenerator */
    private $tokenCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();

        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->userRepository = static::$container->get(UserRepository::class);
        $this->tokenGenerator = static::$container->get(JwtTokenGenerator::class);
        $this->tokenCache = static::$container->get('app.jwt_cache.adapter');
        $this->tokenCacheIdGenerator = static::$container->get(JwtTokenCacheIdGenerator::class);
    }

    public function testIfAccessTokenCanBeRefreshed(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->logInAs($user, $this->kernelBrowser);

        $initialAccessToken = $authenticationResponse['accessToken'];

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => $authenticationResponse['refreshToken']], JSON_THROW_ON_ERROR)
        );

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('accessToken', $decodedResponse);
        self::assertNotSame($initialAccessToken, $decodedResponse['accessToken']);
    }

    public function testItReturnsExceptionIfRefreshTokenIsMissingFromRequest(): void
    {
        $this->kernelBrowser->request(Request::METHOD_POST, '/api/v1/auth/refresh-token');

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame("Missing 'refreshToken' in request body.", $decodedResponse['error']['message']);
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

        self::assertSame(Response::HTTP_BAD_REQUEST, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Invalid token.', $decodedResponse['error']['message']);
    }

    public function testItReturnsExceptionIfUserCannotBeFound(): void
    {
        $tokenForNonExistingUser = $this->tokenGenerator->create(
            (new User())->setUsername('notExisting'),
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

        self::assertSame(Response::HTTP_FORBIDDEN, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Invalid token.', $decodedResponse['error']['message']);
    }

    public function testItReturnsExceptionIfRefreshTokenIsExpired(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->logInAs($user, $this->kernelBrowser);

        Carbon::setTestNow('+2 years');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => $authenticationResponse['refreshToken']], JSON_THROW_ON_ERROR)
        );

        Carbon::setTestNow();

        self::assertSame(Response::HTTP_FORBIDDEN, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Expired token.', $decodedResponse['error']['message']);
    }

    public function testItReturnsExceptionIfRefreshTokenCannotBeFoundInCache(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->logInAs($user, $this->kernelBrowser);

        $refreshToken = (new Parser())->parse($authenticationResponse['refreshToken']);
        $cacheId = $this->tokenCacheIdGenerator->generate($refreshToken);
        $this->tokenCache->deleteItem($cacheId);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/refresh-token',
            [],
            [],
            [],
            json_encode(['refreshToken' => $authenticationResponse['refreshToken']], JSON_THROW_ON_ERROR)
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $this->kernelBrowser->getResponse()->getStatusCode());

        $decodedResponse = json_decode(
            $this->kernelBrowser->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('Expired token.', $decodedResponse['error']['message']);
    }
}
