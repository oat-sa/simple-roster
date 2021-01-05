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
use Symfony\Component\Security\Core\User\UserInterface;

class TokenGenerator
{
    //REGISTERED CLAIMS
    private const IDENTIFIEDBY_CLAIM = 'jti';
    private const ISSUEDAT_CLAIM = 'iat';
    private const EXPIRATION_CLAIM = 'exp';

    //CUSTOM CLAIMS
    private const IDENTIFIER_CLAIM = 'username';
    private const ROLES_CLAIM = 'roles';

    /** @var string */
    private $privateKeyPath;

    /** @var string */
    private $passphrase;

    public function __construct(
        string $jwtPrivateKeyPath,
        string $jwtPassphrase
    ) {
        $this->privateKeyPath = $jwtPrivateKeyPath;
        $this->passphrase = $jwtPassphrase;
    }

    public function create(UserInterface $user, int $ttl): Token
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
        $expiration = $now + $ttl;
        $payload[self::EXPIRATION_CLAIM] = $expiration;

        $generatedToken = $this->generateJWTString($payload);

        return $generatedToken;
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
}
