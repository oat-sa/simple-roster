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

namespace OAT\SimpleRoster\Tests\Integration\Security\Verifier;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

class JwtTokenVerifierTest extends KernelTestCase
{
    /** @var JwtTokenVerifier */
    private $subject;

    /** @var JwtTokenGenerator */
    private $tokenGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->subject = self::getContainer()->get(JwtTokenVerifier::class);
        $this->tokenGenerator = self::getContainer()->get(JwtTokenGenerator::class);
    }

    public function testSuccessfulVerification(): void
    {
        $user = (new User())->setUsername('testUser');

        $token = $this->tokenGenerator->create($user, Request::create('/test'), 'testSubject', 100);

        self::assertTrue($this->subject->isValid($token));
    }

    public function testUnsuccessfulVerification(): void
    {
        self::assertFalse($this->subject->isValid($this->createMock(Token::class)));
    }

    private function createNotValidToken(): Plain
    {
        return new Plain(
            new DataSet(['alg' => 'none'], 'headers'),
            new DataSet([], 'claims'),
            new Signature('hash', 'signature'),
        );
    }

    public function testUnsuccessfulToken(): void
    {
        $token = $this->createNotValidToken();
        self::assertFalse($this->subject->isValid($token));
    }
}
