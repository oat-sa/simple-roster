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

namespace App\Tests\Integration\Security\OAuth;

use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSigner;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class OAuthSignerTest extends TestCase
{
    private const TIMESTAMP = '1549615519';

    public function testSignWithMacSha1Method(): void
    {
        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext(OAuthContext::METHOD_MAC_SHA1);

        $this->assertEquals(
            'VoEu7pwaoCuBG5+59qp1WcHhq/o=',
            $subject->sign($context, 'url', 'method', 'secret')
        );
    }

    public function testSignWithMacSha1MethodContainingSpaces(): void
    {
        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext(OAuthContext::METHOD_MAC_SHA1);

        $this->assertEquals(
            'Apr1WITmq9IoQ3lKJeCfFuNlA6M=',
            $subject->sign($context, 'some url', 'method', 'secret')
        );
    }

    public function testSignWithMacSha1MethodAndAdditionalParameters(): void
    {
        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext(OAuthContext::METHOD_MAC_SHA1);

        $this->assertEquals(
            'Mtx4QCOoeGw3rgUXfn9bWxl8Rhw=',
            $subject->sign($context, 'url', 'method', 'secret', ['param1', 'param2'])
        );
    }

    public function testSignWithInvalidMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Signature method 'invalid' is not supported");

        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext('invalid');

        $subject->sign($context, 'url', 'method', 'secret');
    }

    private function generateOAuthContext(string $signatureMethod): OAuthContext
    {
        return new OAuthContext(
            'bodyHash',
            'consumerKey',
            'nonce',
            $signatureMethod,
            self::TIMESTAMP,
            OAuthContext::VERSION_1_0
        );
    }
}
