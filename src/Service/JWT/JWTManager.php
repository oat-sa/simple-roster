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

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTManager
{
    //REGISTERED CLAIMS
    private const IDENTIFIEDBY_CLAIM = 'jti';
    private const ISSUEDAT_CLAIM = 'iat';
    private const EXPIRATION_CLAIM = 'exp';

    //CUSTOM CLAIMS
    private const IDENTIFIER_CLAIM = 'username';
    private const ROLES_CLAIM = 'roles';


    /** @var CacheItemPoolInterface */
    private $tokenStore;

    /** @var string */
    private $privateKeyPath;

    /** @var string */
    private $publicKeyPath;

    /** @var string */
    private $passphrase;

    public function __construct(
        CacheItemPoolInterface $cache,
        string $privateKeyPath,
        string $publicKeyPath,
        string $passphrase
    ) {
        $this->tokenStore = $cache;
        $this->privateKeyPath = $privateKeyPath;
        $this->publicKeyPath = $publicKeyPath;
        $this->passphrase = $passphrase;
    }

    public function create(UserInterface $user, int $ttl = 0, bool $isRefresh = false): Token
    {
        $payload = [];

        $payload[self::IDENTIFIER_CLAIM] = $user->getUsername();
        $payload[self::ROLES_CLAIM] = $user->getRoles();

        //Identified by claim
        $payload[self::IDENTIFIEDBY_CLAIM] = $user->getUsername();

        $now = Carbon::now()->unix();
        //Issued at claim
        $payload[self::ISSUEDAT_CLAIM] = $now;
        //Expiration time claim
        $payload[self::EXPIRATION_CLAIM] = $now + $ttl;

        $generatedToken = $this->generateJWTString($payload);

        if ($isRefresh) {
            $this->storeTokenInCache($generatedToken);
        }

        return $generatedToken;
    }

    public function getStoredToken(string $identifier): CacheItemInterface
    {
        $identifier = $this->generateCacheId($identifier);

        return $this->tokenStore->getItem($identifier);
    }

    private function generateJWTString(array $payload): Token
    {

        $tokenObject = (new Builder());

        foreach ($payload as $payloadKey => $payloadVal) {
            $tokenObject->withClaim($payloadKey, $payloadVal);
        }

        return $tokenObject->getToken(
            new Sha256(),
            new Key(
                $this->privateKeyPath,
                $this->passphrase
            )
        );
    }

    private function storeTokenInCache(Token $token, int $ttl = 0): void
    {
        $idBase = $token->getClaim(self::IDENTIFIEDBY_CLAIM);

        $cacheId = $this->generateCacheId($idBase);

        $cacheItem = $this->tokenStore->getItem($cacheId);

        $cacheItem
            ->set((string)$token)
            ->expiresAfter($ttl);

        $this->tokenStore->save($cacheItem);
    }

    private function generateCacheId(string $identifier): string
    {
        return sprintf('jwt-token.%s', $identifier);
    }
}
