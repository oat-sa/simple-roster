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

namespace OAT\SimpleRoster\Security\Verifier;

use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Throwable;

class JwtTokenVerifier
{
    private Validator $tokenValidator;
    private string $publicKeyPath;

    public function __construct(Validator $tokenValidator, string $jwtPublicKeyPath)
    {
        $this->tokenValidator = $tokenValidator;
        $this->publicKeyPath = $jwtPublicKeyPath;
    }

    public function isValid(Token $token): bool
    {
        try {
            $signedWithConstraint = new SignedWith(new Sha256(), new Key($this->publicKeyPath));

            return $this->tokenValidator->validate($token, $signedWithConstraint);
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
