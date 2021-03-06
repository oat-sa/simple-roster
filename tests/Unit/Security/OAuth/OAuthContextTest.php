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

namespace OAT\SimpleRoster\Tests\Unit\Security\OAuth;

use JsonSerializable;
use OAT\SimpleRoster\Security\OAuth\OAuthContext;
use PHPUnit\Framework\TestCase;

class OAuthContextTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new OAuthContext(
            'bodyHash',
            'consumerKey',
            'nonce',
            'signatureMethod',
            'timestamp',
            'version'
        );

        self::assertSame('bodyHash', $subject->getBodyHash());
        self::assertSame('consumerKey', $subject->getConsumerKey());
        self::assertSame('nonce', $subject->getNonce());
        self::assertSame('signatureMethod', $subject->getSignatureMethod());
        self::assertSame('timestamp', $subject->getTimestamp());
        self::assertSame('version', $subject->getVersion());
    }

    public function testItIsJsonSerializable(): void
    {
        $subject = new OAuthContext(
            'expectedBodyHash',
            'expectedConsumerKey',
            'expectedNonce',
            'expectedSignatureMethod',
            'expectedTimestamp',
            'expectedVersion'
        );

        self::assertInstanceOf(JsonSerializable::class, $subject);
        self::assertSame(
            [
                'bodyHash' => 'expectedBodyHash',
                'consumerKey' => 'expectedConsumerKey',
                'nonce' => 'expectedNonce',
                'signatureMethod' => 'expectedSignatureMethod',
                'timestamp' => 'expectedTimestamp',
                'version' => 'expectedVersion',
            ],
            $subject->jsonSerialize()
        );
    }
}
