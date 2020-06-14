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

namespace App\Tests\Integration\Repository;

use App\DataTransferObject\AssignmentDto;
use App\DataTransferObject\UserDto;
use App\DataTransferObject\UserDtoCollection;
use App\Entity\User;
use App\Repository\NativeUserRepository;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NativeUserRepositoryTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var NativeUserRepository */
    private $subject;

    /** @var UserRepository */
    private $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = self::$container->get(NativeUserRepository::class);
        $this->userRepository = self::$container->get(UserRepository::class);
    }

    public function testItCanInsertMultipleUsers(): void
    {
        $assignment1 = new AssignmentDto(1, 'test', 1, 1);
        $user1 = new UserDto(1, 'test1', 'test', $assignment1, null);

        $assignment2 = new AssignmentDto(1, 'test', 2, 2);
        $user2 = new UserDto(2, 'test2', 'test', $assignment2, null);

        $userCollection = (new UserDtoCollection())
            ->add($user1)
            ->add($user2);

        $this->subject->insertMultiple($userCollection);

        $user1 = $this->userRepository->find(1);
        $user2 = $this->userRepository->find(2);

        $this->assertInstanceOf(User::class, $user1);
        $this->assertInstanceOf(User::class, $user2);

        $this->assertSame(1, $user1->getId());
        $this->assertSame(2, $user2->getId());
    }

    public function testItCanFindNextAvailableUserIndex(): void
    {
        $this->assertSame(1, $this->subject->findNextAvailableUserIndex());

        $assignment = new AssignmentDto(1, 'test', 1, 1);
        $user = new UserDto(1, 'test1', 'test', $assignment, null);

        $userCollection = (new UserDtoCollection())->add($user);

        $this->subject->insertMultiple($userCollection);

        $this->assertSame(2, $this->subject->findNextAvailableUserIndex());
    }
}
