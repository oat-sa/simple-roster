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
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Throwable;

class JwtTokenAuthenticator extends AbstractAuthenticator
{
    private AuthorizationHeaderTokenExtractor $tokenExtractor;
    private JwtTokenVerifier $tokenVerifier;

    public function __construct(AuthorizationHeaderTokenExtractor $tokenExtractor, JwtTokenVerifier $tokenVerifier)
    {
        $this->tokenExtractor = $tokenExtractor;
        $this->tokenVerifier = $tokenVerifier;
    }

    /**
     * @inheritdoc
     */
    public function supports(Request $request): bool
    {
        return $request->headers->has(AuthorizationHeaderTokenExtractor::AUTHORIZATION_HEADER)
            && $this->tokenExtractor->extract($request) !== '';
    }

    public function authenticate(Request $request): PassportInterface
    {
        try {
            $credentials = $this->tokenExtractor->extract($request);
            $token = (new Parser())->parse($credentials);
        } catch (Throwable $exception) {
            throw new AuthenticationException('Invalid token.', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->tokenVerifier->isValid($token) || !$token->claims()->has('aud')) {
            throw new AuthenticationException('Invalid token.', Response::HTTP_BAD_REQUEST);
        }

        if (!$token->claims()->has('sub') || $token->claims()->get('sub') !== 'accessToken') {
            throw new AuthenticationException('Invalid token.', Response::HTTP_BAD_REQUEST);
        }

        if ($token->isExpired(Carbon::now())) {
            throw new AuthenticationException('Expired token.', Response::HTTP_FORBIDDEN);
        }

        return new SelfValidatingPassport(new UserBadge((string)$token->claims()->get('aud')[0]));
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
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}
