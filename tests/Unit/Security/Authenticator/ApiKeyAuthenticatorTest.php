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

namespace OAT\SimpleRoster\Tests\Unit\Security\Authenticator;

use OAT\SimpleRoster\Security\Authenticator\ApiKeyAuthenticator;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;

class ApiKeyAuthenticatorTest extends TestCase
{
    public function testAuthenticateDoesNotAddRememberMeBadge(): void
    {
        $extractor = $this->createMock(AuthorizationHeaderTokenExtractor::class);
        $extractor
            ->method('extract')
            ->willReturn('valid-api-key');

        $subject = new ApiKeyAuthenticator($extractor, 'valid-api-key');

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer valid-api-key']
        );

        $passport = $subject->authenticate($request);

        self::assertFalse(
            $passport->hasBadge(RememberMeBadge::class),
            'ApiKeyAuthenticator must not support remember-me'
        );
    }

    public function testStartReturnsUnauthorizedResponseOnAuthenticationError(): void
    {
        $subject = new ApiKeyAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            'key'
        );

        $response = $subject->start(
            Request::create('/test', 'GET'),
            new AuthenticationException('whatever')
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $decoded = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('API key authentication failure.', $decoded['error']['message']);
        self::assertTrue($response->headers->has('WWW-Authenticate'));
        self::assertStringContainsString('Bearer realm="SimpleRoster"', (string)$response->headers->get('WWW-Authenticate'));
    }

}
