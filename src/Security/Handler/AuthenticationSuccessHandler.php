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

namespace OAT\SimpleRoster\Security\Handler;

use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /** @var JwtTokenGenerator */
    private $jwtTokenGenerator;

    /** @var JwtTokenCacheIdGenerator */
    private $jwtTokenIdGenerator;

    /** @var CacheItemPoolInterface */
    private $jwtTokenCache;

    /** @var LoggerInterface */
    private $logger;

    /** @var SerializerResponder */
    private $responder;

    /** @var int */
    private $accessTokenTtl;

    /** @var int */
    private $refreshTokenTtl;

    public function __construct(
        JwtTokenGenerator $jwtTokenGenerator,
        JwtTokenCacheIdGenerator $jwtTokenIdGenerator,
        CacheItemPoolInterface $jwtTokenCache,
        LoggerInterface $securityLogger,
        SerializerResponder $responder,
        int $jwtAccessTokenTtl,
        int $jwtRefreshTokenTtl
    ) {
        $this->jwtTokenGenerator = $jwtTokenGenerator;
        $this->jwtTokenIdGenerator = $jwtTokenIdGenerator;
        $this->jwtTokenCache = $jwtTokenCache;
        $this->logger = $securityLogger;
        $this->responder = $responder;
        $this->accessTokenTtl = $jwtAccessTokenTtl;
        $this->refreshTokenTtl = $jwtRefreshTokenTtl;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var UserInterface $user */
        $user = $token->getUser();
        $accessToken = $this->jwtTokenGenerator->create($user, $request, 'accessToken', $this->accessTokenTtl);
        $refreshToken = $this->jwtTokenGenerator->create($user, $request, 'refreshToken', $this->refreshTokenTtl);

        $refreshTokenCacheId = $this->jwtTokenIdGenerator->generate($refreshToken);

        $cacheItem = $this->jwtTokenCache->getItem($refreshTokenCacheId);

        $cacheItem
            ->set((string)$refreshToken)
            ->expiresAfter($refreshToken->getClaim('exp'));

        $this->jwtTokenCache->save($cacheItem);

        $refreshTokenLogContext = [
            'cacheId' => $refreshTokenCacheId,
            'cacheTtl' => $this->refreshTokenTtl,
        ];

        $this->logTokenGeneration($accessToken);
        $this->logTokenGeneration($refreshToken, $refreshTokenLogContext);

        return $this->responder->createJsonResponse([
            'accessToken' => (string)$accessToken,
            'refreshToken' => (string)$refreshToken,
        ]);
    }

    private function logTokenGeneration(Token $token, array $logContext = []): void
    {
        $this->logger->info(
            sprintf(
                "Token '%s' with id '%s' has been generated for user '%s'.",
                $token->getClaim('sub'),
                $token->getClaim('jti'),
                $token->getClaim('aud'),
            ),
            $logContext
        );
    }
}
