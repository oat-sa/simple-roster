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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Security\Lti;

use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\Lti\LoginHintValidator;
use OAT\SimpleRoster\Security\Lti\OidcUserAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OidcUserAuthenticatorTest extends TestCase
{
    /** @var OidcUserAuthenticator */
    private $subject;

    /** @var LoginHintValidator|MockObject */
    private $loginHintValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loginHintValidator = $this->createMock(LoginHintValidator::class);

        $this->subject = new OidcUserAuthenticator($this->loginHintValidator);
    }

    public function testAuthenticate(): void
    {
        $user = (new User())
            ->setUsername('username');

        $this->loginHintValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($user);

        $result = $this->subject->authenticate('loginHint');

        $this->assertSame('username', $result->getUserIdentity()->getIdentifier());
    }

    public function testAuthenticateWhenUserHaveNoUsername(): void
    {
        $this->loginHintValidator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new User());

        $result = $this->subject->authenticate('loginHint');

        $this->assertSame(
            OidcUserAuthenticator::UNDEFINED_USERNAME,
            $result->getUserIdentity()->getIdentifier()
        );
    }
}
