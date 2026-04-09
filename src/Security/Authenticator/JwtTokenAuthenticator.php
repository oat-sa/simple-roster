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
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Throwable;

class JwtTokenAuthenticator extends AbstractAuthenticator
{
    private AuthorizationHeaderTokenExtractor $tokenExtractor;

    private JwtTokenVerifier $tokenVerifier;

    private SerializerResponder $responder;

    private JwtConfiguration $jwtConfig;

    public function __construct(
        AuthorizationHeaderTokenExtractor $tokenExtractor,
        JwtTokenVerifier $tokenVerifier,
        SerializerResponder $responder,
        JwtConfiguration $jwtConfig
    ) {
        $this->tokenExtractor = $tokenExtractor;
        $this->tokenVerifier = $tokenVerifier;
        $this->responder = $responder;
        $this->jwtConfig = $jwtConfig;
    }

    /**
     * @inheritdoc
     */
    public function supports(Request $request): ?bool
    {
        return $request->headers->has(AuthorizationHeaderTokenExtractor::AUTHORIZATION_HEADER)
            && $this->tokenExtractor->extract($request) !== '';
    }

    /**
     * @inheritdoc
     */
    public function authenticate(Request $request): Passport
    {
        $credentials = $this->tokenExtractor->extract($request);

        try {
            $token = $this->jwtConfig->parseJwtCredentials($credentials);
        } catch (Throwable $exception) {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }

        if (!$this->tokenVerifier->isValid($token) || !$token->claims()->has('aud')) {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }

        if (!$token->claims()->has('sub') || $token->claims()->get('sub') !== 'accessToken') {
            throw new CustomUserMessageAuthenticationException('Invalid token.');
        }

        if ($token->isExpired(Carbon::now())) {
            throw new CustomUserMessageAuthenticationException('Expired token.');
        }

        $aud = $token->claims()->get('aud');
        $userIdentifier = is_array($aud) && isset($aud[0]) ? (string)$aud[0] : (string)$aud;

        return new SelfValidatingPassport(new UserBadge($userIdentifier));
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->responder->createJsonResponse(
            $exception->getMessage(),
            Response::HTTP_FORBIDDEN
        );
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
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->responder->createJsonResponse(
            'Full authentication is required to access this resource.',
            Response::HTTP_UNAUTHORIZED
        );
    }
}
