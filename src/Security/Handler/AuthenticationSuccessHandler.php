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

namespace OAT\SimpleRoster\Security\Handler;

use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\JWT\JWTManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /** @var JWTManager */
    private $jwtTokenGenerator;

    /** @var SerializerResponder */
    private $responder;

    /** @var int */
    private $accessTokenTtl;

    /** @var int */
    private $refreshTokenTtl;

    public function __construct(
        JWTManager $jwtTokenGenerator,
        SerializerResponder $responder,
        int $accessTokenTtl,
        int $refreshTokenTtl
    ) {
        $this->jwtTokenGenerator = $jwtTokenGenerator;
        $this->responder = $responder;
        $this->accessTokenTtl = $accessTokenTtl;
        $this->refreshTokenTtl = $refreshTokenTtl;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        /** @var UserInterface $user */
        $user = $token->getUser();
        $accessToken = $this->jwtTokenGenerator->create($user, $this->accessTokenTtl);

        $refreshToken = $this->jwtTokenGenerator->create($user, $this->refreshTokenTtl);
        $this->jwtTokenGenerator->storeTokenInCache($refreshToken, $this->refreshTokenTtl);

        return $this->responder->createJsonResponse([
            'accessToken' => (string)$accessToken,
            'refreshToken' => (string)$refreshToken,
        ]);
    }
}
