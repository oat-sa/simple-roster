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

namespace OAT\SimpleRoster\Security\Listener;

use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Authenticator\JwtConfiguration;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutEventListener
{
    private AuthorizationHeaderTokenExtractor $tokenExtractor;
    private CacheItemPoolInterface $tokenCache;
    private JwtTokenCacheIdGenerator $tokenCacheIdGenerator;
    private LoggerInterface $logger;
    private SerializerResponder $serializerResponder;
    private JwtConfiguration $jwtConfig;

    public function __construct(
        AuthorizationHeaderTokenExtractor $tokenExtractor,
        CacheItemPoolInterface $jwtTokenCache,
        JwtTokenCacheIdGenerator $tokenCacheIdGenerator,
        LoggerInterface $securityLogger,
        SerializerResponder $serializerResponder,
        JwtConfiguration $jwtConfig
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->tokenCache = $jwtTokenCache;
        $this->tokenCacheIdGenerator = $tokenCacheIdGenerator;
        $this->logger = $securityLogger;
        $this->serializerResponder = $serializerResponder;
        $this->jwtConfig = $jwtConfig;
    }

    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $accessToken = $this->tokenExtractor->extract($request);
        $parsedToken = $this->jwtConfig->parseJwtCredentials($accessToken);

        $refreshTokenCacheId = $this->tokenCacheIdGenerator->generate($parsedToken, 'refreshToken');
        $cacheItem = $this->tokenCache->getItem($refreshTokenCacheId);

        if ($cacheItem->isHit()) {
            $this->tokenCache->deleteItem($refreshTokenCacheId);

            $this->logger->info(
                sprintf("Refresh token for user '%s' has been invalidated.", $parsedToken->claims()->get('aud')[0]),
                [
                    'cacheId' => $refreshTokenCacheId,
                ]
            );
        }

        $event->setResponse($this->serializerResponder->createJsonResponse([], JsonResponse::HTTP_NO_CONTENT));
    }
}
