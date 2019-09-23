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

namespace App\Tests\Unit\EventListener\Doctrine;

use App\Entity\User;
use App\EventListener\Doctrine\UserPasswordEncoderListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordEncoderListenerTest extends TestCase
{
    /** @var UserPasswordEncoderListener */
    private $subject;

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
            ->expects($this->never())
            ->method('encodePassword');

        $entity
            ->expects($this->never())
            ->method('setPassword');

        $this->subject->prePersist($entity);
    }

    public function testItCorrectlyUpdatesTheEncodedPasswordUponPrePersist(): void
    {
        $entity = new User();
        $entity->setPlainPassword('password');

        $this
            ->userPasswordEncoderMock
            ->expects($this->once())
            ->method('encodePassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->prePersist($entity);

        $this->assertEquals(
            'encodedPassword',
            $entity->getPassword()
        );
    }

    public function testItCorrectlyUpdatesTheEncodedPasswordUponPreUpdate(): void
    {
        $entity = new User();
        $entity->setPlainPassword('password');

        $this
            ->userPasswordEncoderMock
            ->expects($this->once())
            ->method('encodePassword')
            ->with($entity, 'password')
            ->willReturn('encodedPassword');

        $this->subject->preUpdate($entity);

        $this->assertEquals(
            'encodedPassword',
            $entity->getPassword()
        );
    }
}
