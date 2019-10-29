<?php

declare(strict_types=1);

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

namespace App\Security\Authenticator;

use App\Entity\User;
use App\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    public const AUTH_REALM = 'SimpleRoster';

    /** @var AuthorizationHeaderTokenExtractor */
    private $tokenExtractor;

    /** @var string */
    private $appApiKey;

    public function __construct(AuthorizationHeaderTokenExtractor $tokenExtractor, string $appApiKey)
    {
        $this->tokenExtractor = $tokenExtractor;
        $this->appApiKey = $appApiKey;
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has(AuthorizationHeaderTokenExtractor::AUTHORIZATION_HEADER);
    }

    public function getCredentials(Request $request)
    {
        return [
            'token' => $this->tokenExtractor->extract($request),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return new User();
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $credentials['token'] === $this->appApiKey;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $this->createUnauthorizedHttpException($exception);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw $this->createUnauthorizedHttpException($authException);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function createUnauthorizedHttpException(?AuthenticationException $exception): UnauthorizedHttpException
    {
        $message = 'API key authentication failure.';

        return new UnauthorizedHttpException(
            sprintf('Bearer realm="%s", error="invalid_api_key", error_description="%s"', static::AUTH_REALM, $message),
            $message,
            $exception
        );
    }
}
