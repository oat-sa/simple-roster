<?php

/*
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

namespace OAT\SimpleRoster\Security\Generator;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtTokenGenerator
{
    /** @var UuidFactoryInterface */
    private $uuidFactory;

    /** @var Builder */
    private $builder;

    /** @var string */
    private $privateKeyPath;

    /** @var string */
    private $passphrase;

    public function __construct(
        UuidFactoryInterface $uuidFactory,
        Builder $builder,
        string $jwtPrivateKeyPath,
        string $jwtPassphrase
    ) {
        $this->uuidFactory = $uuidFactory;
        $this->builder = $builder;
        $this->privateKeyPath = $jwtPrivateKeyPath;
        $this->passphrase = $jwtPassphrase;
    }

    public function create(UserInterface $user, Request $request, string $subjectClaim, int $ttl): Token
    {
        $currentTime = Carbon::now()->unix();

        return $this->builder
            // iss claim
            ->issuedBy($request->getSchemeAndHttpHost())
            // sub claim
            ->relatedTo($subjectClaim)
            // aud claim
            ->permittedFor($user->getUsername())
            // jti claim
            ->identifiedBy($this->uuidFactory->uuid4()->toString())
            // iat claim
            ->issuedAt($currentTime)
            // nbf claim
            ->canOnlyBeUsedAfter($currentTime)
            // exp claim
            ->expiresAt($currentTime + $ttl)
            ->getToken(new Sha256(), new Key($this->privateKeyPath, $this->passphrase));
    }
}
