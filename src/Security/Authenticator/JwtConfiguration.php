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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Security\Authenticator;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use ReflectionClass;

class JwtConfiguration
{
    /** @var Configuration object */
    private object $jwtConfig;

    /** @var InMemory object */
    private object $jwtInMemoryFilePath;

    private string $privateKeyPath;
    private string $passphrase;
    private string $publicKeyPath;

    public function __construct(
        string $jwtPrivateKeyPath,
        string $jwtPassphrase,
        string $jwtPublicKeyPath
    ) {
        $this->privateKeyPath = $jwtPrivateKeyPath;
        $this->passphrase = $jwtPassphrase;
        $this->publicKeyPath = $jwtPublicKeyPath;

        $this->jwtConfig  = (new ReflectionClass(Configuration::class))->newInstanceWithoutConstructor();
        $this->jwtInMemoryFilePath = (new ReflectionClass(InMemory::class))->newInstanceWithoutConstructor();
    }

    public function initialise(): Configuration
    {
        return $this->jwtConfig->forAsymmetricSigner(
            new Sha256(),
            $this->jwtInMemoryFilePath->file($this->privateKeyPath, $this->passphrase),
            $this->jwtInMemoryFilePath->file($this->publicKeyPath, $this->passphrase)
        );
    }

    public function parseJwtCredentials(string $credentials): Plain
    {
        $parsedToken =  $this->jwtConfig->forUnsecuredSigner()
            ->parser()->parse($credentials);
        assert($parsedToken instanceof Token\Plain);

        return $parsedToken;
    }

    public function verifyJwtKey(): SignedWith
    {
        $key = $this->jwtInMemoryFilePath->file($this->publicKeyPath);

        return new SignedWith(new Sha256(), $key);
    }
}
