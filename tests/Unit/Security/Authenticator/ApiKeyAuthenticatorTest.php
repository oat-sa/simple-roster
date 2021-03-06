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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiKeyAuthenticatorTest extends TestCase
{
    public function testItDoesNotSupportRememberMe(): void
    {
        $subject = new ApiKeyAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            'key'
        );

        self::assertFalse($subject->supportsRememberMe());
    }

    public function testItThrowsExceptionUnauthorizedExceptionOnAuthenticationError(): void
    {
        $subject = new ApiKeyAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            'key'
        );

        /** @var Request|MockObject $request */
        $request = $this->createMock(Request::class);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('API key authentication failure.');

        $subject->start($request, new AuthenticationException());
    }
}
