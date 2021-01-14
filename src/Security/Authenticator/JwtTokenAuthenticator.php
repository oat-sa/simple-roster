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

namespace OAT\SimpleRoster\Security\Authenticator;

use Carbon\Carbon;
use Lcobucci\JWT\Parser;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Throwable;

class JwtTokenAuthenticator extends AbstractGuardAuthenticator
{
    /** @var AuthorizationHeaderTokenExtractor */
    private $tokenExtractor;

    /** @var JwtTokenVerifier */
    private $tokenVerifier;

    /** @var SerializerResponder */
    private $responder;

    public function __construct(
        AuthorizationHeaderTokenExtractor $tokenExtractor,
        JwtTokenVerifier $tokenVerifier,
        SerializerResponder $responder
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->tokenVerifier = $tokenVerifier;
        $this->responder = $responder;
    }

    /**
     * @inheritdoc
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has(AuthorizationHeaderTokenExtractor::AUTHORIZATION_HEADER);
    }

    /**
     * @inheritdoc
     */
    public function getCredentials(Request $request): string
    {
        return $this->tokenExtractor->extract($request);
    }

    /**
     * @inheritdoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider): UserInterface
    {
        try {
            $token = (new Parser())->parse($credentials);
        } catch (Throwable $exception) {
            throw new AuthenticationException('Invalid token.', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->tokenVerifier->isValid($token) || !$token->hasClaim('aud')) {
            throw new AuthenticationException('Invalid token.', Response::HTTP_BAD_REQUEST);
        }

        if (!$token->hasClaim('sub') || $token->getClaim('sub') !== 'accessToken') {
            throw new AuthenticationException('Invalid token.', Response::HTTP_BAD_REQUEST);
        }

        if ($token->isExpired(Carbon::now())) {
            throw new AuthenticationException('Expired token.', Response::HTTP_FORBIDDEN);
        }

        return $userProvider->loadUserByUsername((string)$token->getClaim('aud'));
    }

    /**
     * @inheritdoc
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw $exception;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return $this->responder->createJsonResponse(
            'Full authentication is required to access this resource.',
            Response::HTTP_UNAUTHORIZED
        );
    }
}
