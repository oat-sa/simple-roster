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

namespace OAT\SimpleRoster\Security\Authenticator;

use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Uid\UuidV6;

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

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return new User(new UuidV6(), 'apiUser', 'notUsed');
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $credentials['token'] === $this->appApiKey;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?Response
    {
        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $this->createUnauthorizedHttpException($exception);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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
