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

use Monolog\Logger;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Tests\Traits\ApiTestingTrait;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\LoggerTestingTrait;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutActionTest extends WebTestCase
{
    use ApiTestingTrait;
    use DatabaseTestingTrait;
    use LoggerTestingTrait;

    /** @var UserRepository */
    private $userRepository;

    /** @var JwtTokenCacheIdGenerator */
    private $jwtTokenCacheIdGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelBrowser = self::createClient();
        $this->userRepository = self::$container->get(UserRepository::class);
        $this->jwtTokenCacheIdGenerator = self::$container->get(JwtTokenCacheIdGenerator::class);

        $this->setUpDatabase();
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $this->setUpTestLogHandler('security');
    }

    public function testItLogsOutProperlyTheUser(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/logout',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $this->authenticateAs($user)->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_NO_CONTENT);
    }

    public function testItRemovesTokenOnLogout(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');
        $authenticationResponse = $this->authenticateAs($user);
        $refreshToken = $authenticationResponse->getRefreshToken();

        /** @var CacheItemPoolInterface $cachePool */
        $cachePool = self::$container->get('app.jwt_cache.adapter');

        $refreshTokenCacheItem = $cachePool->getItem($this->jwtTokenCacheIdGenerator->generate($refreshToken));

        self::assertTrue($refreshTokenCacheItem->isHit());

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/logout',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $authenticationResponse->getAccessToken()]
        );

        $this->assertApiStatusCode(Response::HTTP_NO_CONTENT);

        $refreshTokenCacheItem = $cachePool->getItem($this->jwtTokenCacheIdGenerator->generate($refreshToken));

        self::assertFalse($refreshTokenCacheItem->isHit());
    }

    public function testItLogsRefreshTokenInvalidation(): void
    {
        $user = $this->userRepository->findByUsernameWithAssignments('user1');

        $authenticationResponse = $this->authenticateAs($user);
        $refreshToken = $authenticationResponse->getRefreshToken();

        self::ensureKernelShutdown();
        $this->kernelBrowser = self::createClient();

        $this->setUpTestLogHandler('security');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/logout',
            [],
            [],
            ['HTTP_Authorization' => 'Bearer ' . $authenticationResponse->getAccessToken()]
        );

        $this->assertHasLogRecord([
            'message' => "Refresh token for user 'user1' has been invalidated.",
            'context' => [
                'cacheId' => $this->jwtTokenCacheIdGenerator->generate($refreshToken),
            ],
        ], Logger::INFO);
    }
}
