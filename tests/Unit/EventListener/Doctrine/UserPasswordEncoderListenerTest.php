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

namespace OAT\SimpleRoster\Tests\Unit\EventListener\Doctrine;

use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\EventListener\Doctrine\UserPasswordEncoderListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

class UserPasswordEncoderListenerTest extends TestCase
{
    private UserPasswordEncoderListener $subject;

    /** @var UserPasswordHasher|MockObject */
    private $userPasswordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $this->subject = new UserPasswordEncoderListener($this->userPasswordHasher);
    }

    public function testItDoesNothingIfTheUserPlainPasswordIsEmpty(): void
    {
        $entity = $this->createMock(User::class);

        $this
            ->userPasswordHasher
            ->expects(self::never())
            ->method('hashPassword');

        $entity
            ->expects(self::never())
            ->method('setPassword');

        $this->subject->prePersist($entity);
    }

    public function testItCorrectlyUpdatesTheEncodedPasswordUponPrePersist(): void
    {
        $user = new User();
        $user->setPlainPassword('password');

        $this
            ->userPasswordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with($user, 'password')
            ->willReturn('encodedPassword');

        $this->subject->prePersist($user);

        self::assertSame(
            'encodedPassword',
            $user->getPassword()
        );
    }

    public function testItCorrectlyUpdatesTheEncodedPasswordUponPreUpdate(): void
    {
        $entity = new User();
        $entity->setPlainPassword('password');

        $this
            ->userPasswordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->preUpdate($entity);

        self::assertSame(
            'encodedPassword',
            $entity->getPassword()
        );
    }
}
