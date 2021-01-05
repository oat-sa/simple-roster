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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Security\Handler;

use Lcobucci\JWT\Parser;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use OAT\SimpleRoster\Service\JWT\TokenStorage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /** @var AuthorizationHeaderTokenExtractor $tokenExtractor */
    private $tokenExtractor;

    /** @var TokenStorage $tokenStorage */
    private $tokenStorage;

    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(
        AuthorizationHeaderTokenExtractor $tokenExtractor,
        TokenStorage $jwtStorage,
        SerializerResponder $serializerResponder
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->tokenStorage = $jwtStorage;
        $this->serializerResponder = $serializerResponder;
    }

    public function onLogoutSuccess(Request $request)
    {
        $credentials = $this->tokenExtractor->extract($request);

        $token = (new Parser())->parse($credentials);

        $username = $token->getClaim('username');

        $this->tokenStorage->removeStoredToken($username);

        return $this->serializerResponder->createJsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
