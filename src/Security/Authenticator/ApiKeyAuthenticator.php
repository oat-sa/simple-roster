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
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public const AUTH_REALM = 'SimpleRoster';

    private AuthorizationHeaderTokenExtractor $tokenExtractor;
    private string $appApiKey;

    public function __construct(AuthorizationHeaderTokenExtractor $tokenExtractor, string $appApiKey)
    {
        $this->tokenExtractor = $tokenExtractor;
        $this->appApiKey = $appApiKey;
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has(AuthorizationHeaderTokenExtractor::AUTHORIZATION_HEADER);
    }

    public function authenticate(Request $request): PassportInterface
    {
        $apiToken = $this->tokenExtractor->extract($request);
        if ($apiToken !== $this->appApiKey) {
            throw $this->createUnauthorizedHttpException(null);
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $apiToken,
                static function () {
                    return new User();
                }
            )
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw $this->createUnauthorizedHttpException($exception);
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
