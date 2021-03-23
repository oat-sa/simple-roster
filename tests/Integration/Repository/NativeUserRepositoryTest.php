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

namespace OAT\SimpleRoster\Tests\Integration\Repository;

use OAT\SimpleRoster\DataTransferObject\UserDto;
use OAT\SimpleRoster\DataTransferObject\UserDtoCollection;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\NativeUserRepository;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV6;

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
        $userId1 = new UuidV6('00000001-0000-6000-0000-000000000000');
        $userId2 = new UuidV6('00000002-0000-6000-0000-000000000000');

        $user1 = new UserDto($userId1, 'test1', 'test');
        $user2 = new UserDto($userId2, 'test2', 'test');

        $userCollection = (new UserDtoCollection())
            ->add($user1)
            ->add($user2);

        $this->subject->insertMultiple($userCollection);

        $users = $this->getRepository(User::class)->findAll();
        self::assertCount(2, $users);

        self::assertTrue($userId1->equals($users[0]->getId()));
        self::assertTrue($userId2->equals($users[1]->getId()));
    }

    public function testItCanFindUsersByUsername(): void
    {
        $this->loadFixtureByFilename('100usersWithAssignments.yml');

        $expectedUsernames = ['user_1', 'user_2', 'user_3', 'user_4', 'user_5'];

        $users = $this->subject->findUsernames($expectedUsernames);

        self::assertCount(5, $users);

        foreach ($expectedUsernames as $expectedUsername) {
            self::assertContains($expectedUsername, $expectedUsernames);
        }
    }
}
