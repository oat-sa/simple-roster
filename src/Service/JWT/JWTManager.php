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

class JWTManager
{
    /** @var string */
    private $privateKeyPath;

    /** @var string */
    private $publicKeyPath;

    /** @var string */
    private $passphrase;

    public function __construct(
        string $privateKeyPath,
        string $publicKeyPath,
        string $passphrase
    ) {
        $this->privateKeyPath = $privateKeyPath;
        $this->publicKeyPath = $publicKeyPath;
        $this->passphrase = $passphrase;
    }

    public function create(UserInterface $user, int $ttl = 0): Token
    {
        $payload = [];

        $payload['roles'] = $user->getRoles();
        $payload['username'] = $user->getUsername();

        //Identified by claim
        $payload['jti'] = $user->getUsername();

        $now = Carbon::now()->unix();
        //Issued at claim
        $payload['iat'] = $now;
        //Expiration time claim
        $payload['exp'] = $now + $ttl;

        return $this->generateJWTString($payload);
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
