<?php

namespace OAT\SimpleRoster\Security\Listener;

use Lcobucci\JWT\Parser;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutEventListener
{
    /** @var AuthorizationHeaderTokenExtractor */
    private $tokenExtractor;

    /** @var CacheItemPoolInterface */
    private $tokenCache;

    /** @var JwtTokenCacheIdGenerator */
    private $tokenCacheIdGenerator;

    /** @var LoggerInterface */
    private $logger;

    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(
        AuthorizationHeaderTokenExtractor $tokenExtractor,
        CacheItemPoolInterface $jwtTokenCache,
        JwtTokenCacheIdGenerator $tokenCacheIdGenerator,
        LoggerInterface $securityLogger,
        SerializerResponder $serializerResponder
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->tokenCache = $jwtTokenCache;
        $this->tokenCacheIdGenerator = $tokenCacheIdGenerator;
        $this->logger = $securityLogger;
        $this->serializerResponder = $serializerResponder;
    }

    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $accessToken = $this->tokenExtractor->extract($request);
        $parsedToken = (new Parser())->parse($accessToken);

        $refreshTokenCacheId = $this->tokenCacheIdGenerator->generate($parsedToken, 'refreshToken');
        $cacheItem = $this->tokenCache->getItem($refreshTokenCacheId);

        if ($cacheItem->isHit()) {
            $this->tokenCache->deleteItem($refreshTokenCacheId);

            $this->logger->info(
                sprintf("Refresh token for user '%s' has been invalidated.", $parsedToken->getClaim('aud')),
                [
                    'cacheId' => $refreshTokenCacheId,
                ]
            );
        }

        $event->setResponse($this->serializerResponder->createJsonResponse([], JsonResponse::HTTP_NO_CONTENT));
    }

}
