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

namespace OAT\SimpleRoster\Request\ParamConverter;

use Lcobucci\JWT\Token\Plain;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use OAT\SimpleRoster\Security\Authenticator\JwtConfiguration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class JwtTokenParamConverter implements ParamConverterInterface
{
    private JwtTokenVerifier $tokenVerifier;

    private JwtConfiguration $jwtConfig;

    public function __construct(
        JwtTokenVerifier $tokenVerifier,
        JwtConfiguration $jwtConfig
    ) {
        $this->tokenVerifier = $tokenVerifier;
        $this->jwtConfig = $jwtConfig;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $decodedRequestBody = json_decode($request->getContent(), true);
        if (!isset($decodedRequestBody['refreshToken'])) {
            throw new BadRequestHttpException("Missing 'refreshToken' in request body.");
        }

        try {
            $refreshToken = $this->jwtConfig->parseJwtCredentials($decodedRequestBody['refreshToken']);
            $this->tokenVerifier->isValid($refreshToken);
        } catch (Throwable $exception) {
            throw new BadRequestHttpException('Invalid token.');
        }

        $request->attributes->set($configuration->getName(), $refreshToken);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return Plain::class === $configuration->getClass();
    }
}
