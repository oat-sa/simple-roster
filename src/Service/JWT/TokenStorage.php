<?php

namespace OAT\SimpleRoster\Service\JWT;

use Lcobucci\JWT\Token;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class TokenStorage
{
    private const IDENTIFIEDBY_CLAIM = 'jti';

    /** @var CacheItemPoolInterface */
    private $tokenStore;

    /** @var TokenIdGenerator $tokenIdGenerator */
    private $tokenIdGenerator;

    public function __construct(
        CacheItemPoolInterface $cache,
        TokenIdGenerator $idGenerator
    ) {
        $this->tokenStore = $cache;
        $this->tokenIdGenerator = $idGenerator;
    }

    public function storeTokenInCache(Token $token, int $ttl): void
    {
        $idBase = $token->getClaim(self::IDENTIFIEDBY_CLAIM);

        $cacheId = $this->tokenIdGenerator->generateCacheId($idBase);

        $cacheItem = $this->tokenStore->getItem($cacheId);

        $cacheItem
            ->set((string)$token)
            ->expiresAfter($ttl);

        $this->tokenStore->save($cacheItem);
    }

    public function getStoredToken(string $identifier): CacheItemInterface
    {
        $identifier = $this->tokenIdGenerator->generateCacheId($identifier);

        return $this->tokenStore->getItem($identifier);
    }

    public function removeStoredToken(string $identifier): bool
    {
        $identifier = $this->tokenIdGenerator->generateCacheId($identifier);

        return $this->tokenStore->deleteItem($identifier);
    }
}
