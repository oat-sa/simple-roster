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

namespace OAT\SimpleRoster\Tests\Integration\Security\Provider;

use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Security\Provider\UserProvider;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    /** @var UserProvider */
    private $subject;

    /** @var RequestStack|MockObject */
    private $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);

        $this->requestStack = $this->createMock(RequestStack::class);

        $this->subject = new UserProvider($userRepository, $this->requestStack);
    }

    public function testItThrowsUsernameNotFoundExceptionWhenLoadingUserWithInvalidUser(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage("Username 'invalid' does not exist");

        $this->subject->loadUserByUsername('invalid');
    }

    public function testItThrowsUnsupportedUserExceptionWhenRefreshingInvalidUserImplementation(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Invalid user class');

        $this->prepareRequestStackMock(0, 'route');

        $this->subject->refreshUser($this->createNonSupportedUserInterfaceImplementation());
    }

    public function testItThrowsUsernameNotFoundExceptionWhenRefreshingInvalidUser(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage("User 'invalid' could not be reloaded");

        $this->prepareRequestStackMock(1, 'route');

        $this->subject->refreshUser((new User())->setUsername('invalid'));
    }

    public function testItDoesNotRefreshForLogout(): void
    {
        $this->prepareRequestStackMock(1, 'logout');

        $toRefreshUser = (new User())->setUsername('invalid');
        $refreshedUser = $this->subject->refreshUser($toRefreshUser);

        self::assertSame($toRefreshUser, $refreshedUser);
    }

    public function testItSupportsUserClassImplementations(): void
    {
        self::assertTrue($this->subject->supportsClass(User::class));
        self::assertFalse($this->subject->supportsClass('invalid'));
    }

    private function createNonSupportedUserInterfaceImplementation(): UserInterface
    {
        return new class () implements UserInterface
        {
            public function getRoles()
            {
                return [];
            }

            public function getPassword()
            {
                return 'password';
            }

            public function getSalt()
            {
                return 'salt';
            }

            public function getUsername()
            {
                return 'invalid';
            }

            /**
             * @return void
             */
            public function eraseCredentials()
            {
            }
        };
    }

    private function prepareRequestStackMock(int $expectedCalls, string $expectedRoute): void
    {
        $this->requestStack
            ->expects(self::exactly($expectedCalls))
            ->method('getCurrentRequest')
            ->willReturn(new Request([], [], ['_route' => $expectedRoute]));
    }
}
