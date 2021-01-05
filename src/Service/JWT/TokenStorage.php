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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

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
