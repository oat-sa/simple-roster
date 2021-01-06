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

namespace OAT\SimpleRoster\Action\Security;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifierInterface;
use OAT\SimpleRoster\Service\JWT\TokenGenerator;
use OAT\SimpleRoster\Service\JWT\TokenStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RefreshAccessTokenAction
{
    /** @var TokenGenerator $generator */
    private $generator;

    /** @var TokenStorage $storage */
    private $storage;

    /** @var UserRepository */
    private $userRepository;

    /** @var JwtTokenVerifierInterface */
    private $tokenVerifier;

    /** @var SerializerResponder */
    private $responder;

    /** @var int */
    private $accessTokenTtl;

    public function __construct(
        TokenGenerator $generator,
        TokenStorage $storage,
        UserRepository $userRepository,
        JwtTokenVerifierInterface $tokenVerifier,
        SerializerResponder $responder,
        int $jwtAccessTokenTtl
    ) {
        $this->generator = $generator;
        $this->storage = $storage;
        $this->userRepository = $userRepository;
        $this->tokenVerifier = $tokenVerifier;
        $this->responder = $responder;
        $this->accessTokenTtl = $jwtAccessTokenTtl;
    }

    public function __invoke(Token $refreshToken): Response
    {
        try {
            $username = $refreshToken->getClaim('username');
        } catch (\Throwable $exception) {
            throw new ConflictHttpException('Invalid token. User claim is missing');
        }

        try {
            $user = $this->userRepository->findOneByUsername($username);
        } catch (\Throwable $exception) {
            throw new ConflictHttpException('Invalid token. User not found');
        }

        $cachedToken = $this->storage->getStoredToken($username);

        if (
            !$cachedToken->isHit()
            || $cachedToken->get() !== (string)$refreshToken
        ) {
            throw new ConflictHttpException('Refresh token is incorrect.');
        }

        $accessToken = $this->generator->create($user, $this->accessTokenTtl);

        return $this->responder->createJsonResponse(['accessToken' => (string)$accessToken]);
    }
}
