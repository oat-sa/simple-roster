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
use OAT\SimpleRoster\Security\Authenticator\JwtConfiguration;use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class JwtTokenParamConverter implements ValueResolverInterface
{
    public function __construct(
        private readonly JwtTokenVerifier $tokenVerifier,
        private readonly JwtConfiguration $jwtConfig,
    ) {
    }
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== Plain::class) {
            return [];
        }

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

        return [$refreshToken];
    }
}
