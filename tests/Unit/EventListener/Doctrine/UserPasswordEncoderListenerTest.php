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
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Uid\UuidV6;

class UserPasswordEncoderListenerTest extends TestCase
{
    private UserPasswordEncoderListener $subject;

    /** @var UserPasswordEncoderInterface|MockObject */
    private $userPasswordEncoderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userPasswordEncoderMock = $this->createMock(UserPasswordEncoderInterface::class);
        $this->subject = new UserPasswordEncoderListener($this->userPasswordEncoderMock);
    }

    public function testItDoesNothingIfTheUserPlainPasswordIsEmpty(): void
    {
        $entity = $this->createMock(User::class);

        $this
            ->userPasswordEncoderMock
            ->expects(self::never())
            ->method('encodePassword');

        $entity
            ->expects(self::never())
            ->method('setPassword');

        $this->subject->prePersist($entity);
    }

    public function testItCorrectlyUpdatesTheEncodedPasswordUponPrePersist(): void
    {
        $entity = new User(new UuidV6(), 'testUser', 'password');

        $this
            ->userPasswordEncoderMock
            ->expects(self::once())
            ->method('encodePassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->prePersist($entity);

        self::assertSame(
            'encodedPassword',
            $entity->getPassword()
        );
    }

    public function testItCorrectlyUpdatesTheEncodedPasswordUponPreUpdate(): void
    {
        $entity = new User(new UuidV6(), 'testUser', 'password');

        $this
            ->userPasswordEncoderMock
            ->expects(self::once())
            ->method('encodePassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->preUpdate($entity);

        self::assertSame(
            'encodedPassword',
            $entity->getPassword()
        );
    }
}
