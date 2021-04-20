<?php

/*
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

namespace OAT\SimpleRoster\Tests\Unit\Security\Generator;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\DataSet;
use OAT\SimpleRoster\Security\Generator\JwtTokenCacheIdGenerator;
use PHPUnit\Framework\TestCase;

class JwtTokenCacheIdGeneratorTest extends TestCase
{
    public function testCacheIdGeneration(): void
    {
        $subject = new JwtTokenCacheIdGenerator();

        $claims = new DataSet(
            [
                'sub' => 'testSubject',
                'aud' => ['testUsername'],
            ],
            'test'
        );

        $token = $this->createMock(Token::class);
        $token
            ->method('claims')
            ->willReturn($claims);

        self::assertSame('jwt.testSubject.testUsername', $subject->generate($token));
    }

    public function testIfSubjectClaimCanBeForced(): void
    {
        $subject = new JwtTokenCacheIdGenerator();

        $claims = new DataSet(['aud' => ['testUsername']], 'test');

        $token = $this->createMock(Token::class);
        $token
            ->expects(self::once())
            ->method('claims')
            ->willReturn($claims);

        self::assertSame(
            'jwt.expectedSubject.testUsername',
            $subject->generate($token, 'expectedSubject')
        );
    }
}
