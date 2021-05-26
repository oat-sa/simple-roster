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

namespace OAT\SimpleRoster\Action\Security;

use Carbon\Carbon;
use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class RefreshAccessTokenAction
{
    /** @var JwtTokenGenerator */
    private JwtTokenGenerator $generator;

    /** @var UserRepository */
    private UserRepository $userRepository;

    /** @var CacheItemPoolInterface */
    private CacheItemPoolInterface $tokenCache;

    /** @var JwtTokenCacheIdGenerator */
    private JwtTokenCacheIdGenerator $tokenCacheIdGenerator;

    /** @var SerializerResponder */
    private SerializerResponder $responder;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var int */
    private int $accessTokenTtl;

    public function __construct(
        JwtTokenGenerator $generator,
        UserRepository $userRepository,
        CacheItemPoolInterface $jwtTokenCache,
        JwtTokenCacheIdGenerator $jwtTokenCacheIdGenerator,
        SerializerResponder $responder,
        LoggerInterface $securityLogger,
        int $jwtAccessTokenTtl
    ) {
        $this->generator = $generator;
        $this->userRepository = $userRepository;
        $this->tokenCache = $jwtTokenCache;
        $this->tokenCacheIdGenerator = $jwtTokenCacheIdGenerator;
        $this->responder = $responder;
        $this->logger = $securityLogger;
        $this->accessTokenTtl = $jwtAccessTokenTtl;
    }

    public function __invoke(Request $request, Token $refreshToken): Response
    {
        try {
            $user = $this->userRepository->findByUsernameWithAssignments($refreshToken->getClaim('aud'));
        } catch (Throwable $exception) {
            throw new AccessDeniedHttpException('Invalid token.');
        }

        $cacheId = $this->tokenCacheIdGenerator->generate($refreshToken);
        $refreshTokenCacheItem = $this->tokenCache->getItem($cacheId);

        if (
            !$refreshTokenCacheItem->isHit()
            || $refreshTokenCacheItem->get() !== (string)$refreshToken
            || $refreshToken->isExpired(Carbon::now())
        ) {
            throw new AccessDeniedHttpException('Expired token.');
        }

        $accessToken = $this->generator->create($user, $request, 'accessToken', $this->accessTokenTtl);

        $this->logger->info(
            sprintf(
                "Access token has been refreshed for user '%s'. (token id = '%s')",
                $accessToken->getClaim('aud'),
                $accessToken->getClaim('jti'),
            )
        );

        return $this->responder->createJsonResponse(['accessToken' => (string)$accessToken]);
    }
}
