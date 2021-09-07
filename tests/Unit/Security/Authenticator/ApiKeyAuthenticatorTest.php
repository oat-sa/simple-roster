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
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiKeyAuthenticatorTest extends TestCase
{
    public function testItSupportsRequestWithAuthorizationHeader(): void
    {
        $subject = new ApiKeyAuthenticator($this->createMock(AuthorizationHeaderTokenExtractor::class), 'testApiKey');

        self::assertFalse($subject->supports(Request::create('/test')));

        self::assertTrue(
            $subject->supports(
                Request::create(
                    '/test',
                    'GET',
                    [],
                    [],
                    [],
                    ['HTTP_AUTHORIZATION' => 'Bearer TestToken'],
                )
            )
        );
    }

    public function testItThrowsUnauthorizedExceptionIfApiKeysAreNotMatching(): void
    {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('API key authentication failure.');

        $subject = new ApiKeyAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            'expectedTestApiKey'
        );

        $subject->authenticate(
            Request::create(
                '/test',
                'GET',
                [],
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer NotTheExpectedToken'],
            )
        );
    }

    public function testSuccessfulAuthentication(): void
    {
        $tokenExtractor = $this->createMock(AuthorizationHeaderTokenExtractor::class);
        $tokenExtractor
            ->method('extract')
            ->willReturn('testApiKey');

        $subject = new ApiKeyAuthenticator($tokenExtractor, 'testApiKey');

        $passport = $subject->authenticate(
            Request::create(
                '/test',
                'GET',
                [],
                [],
                [],
                ['HTTP_AUTHORIZATION' => 'Bearer testApiKey'],
            )
        );

        self::assertTrue($passport->hasBadge(UserBadge::class));
    }

    public function testItAllowsTheRequestToContinueOnAuthenticationSuccess(): void
    {
        $subject = new ApiKeyAuthenticator($this->createMock(AuthorizationHeaderTokenExtractor::class), 'testApiKey');

        self::assertNull(
            $subject->onAuthenticationSuccess(
                $this->createMock(Request::class),
                $this->createMock(TokenInterface::class),
                'providerKey'
            )
        );
    }

    public function testItThrowsUnauthorizedExceptionOnAuthenticationFailure(): void
    {
        $subject = new ApiKeyAuthenticator($this->createMock(AuthorizationHeaderTokenExtractor::class), 'testApiKey');

        try {
            $expectedException = new AuthenticationException('Something went wrong');
            $subject->onAuthenticationFailure($this->createMock(Request::class), $expectedException);

            self::fail('Unauthorized HTTP exception was expected to be thrown.');
        } catch (UnauthorizedHttpException $httpException) {
            self::assertSame('API key authentication failure.', $httpException->getMessage());
            self::assertSame([
                'WWW-Authenticate' => 'Bearer realm="SimpleRoster", error="invalid_api_key", ' .
                    'error_description="API key authentication failure."',
            ], $httpException->getHeaders());
        }
    }
}
