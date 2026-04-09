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
use DateTimeImmutable;
use Lcobucci\JWT\Token\Plain;
use OAT\SimpleRoster\Security\Authenticator\JwtConfiguration;
use Ramsey\Uuid\UuidFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtTokenGenerator
{
    private UuidFactoryInterface $uuidFactory;
    private JwtConfiguration $jwtConfig;

    public function __construct(
        UuidFactoryInterface $uuidFactory,
        JwtConfiguration $jwtConfig
    ) {
        $this->uuidFactory = $uuidFactory;
        $this->jwtConfig = $jwtConfig;
    }

    public function create(UserInterface $user, Request $request, string $subjectClaim, int $ttl): Plain
    {
        $currentTime = Carbon::now()->unix();
        $currentTimeAsDateTime = (new DateTimeImmutable())->setTimestamp($currentTime);

        $jwtConfigInitialize = $this->jwtConfig->initialise();

        return  $jwtConfigInitialize->builder()
            ->issuedBy($request->getSchemeAndHttpHost())
            ->permittedFor($user->getUserIdentifier())
            ->identifiedBy($this->uuidFactory->uuid4()->toString())
            ->relatedTo($subjectClaim)
            ->issuedAt($currentTimeAsDateTime)
            ->canOnlyBeUsedAfter($currentTimeAsDateTime)
            ->expiresAt((new DateTimeImmutable())->setTimestamp($currentTime + $ttl))
            ->getToken($jwtConfigInitialize->signer(), $jwtConfigInitialize->signingKey());
    }
}
