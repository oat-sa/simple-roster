<?php declare(strict_types=1);

namespace App\Tests\Integration\Security\Provider;

use App\Entity\User;
use App\Security\Provider\UserProvider;
use App\Repository\UserRepository;
use App\Tests\Traits\DatabaseFixturesTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends KernelTestCase
{
    use DatabaseFixturesTrait;

    /** @var UserProvider */
    private $subject;

    /** @var RequestStack|MockObject */
    private $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->assertSame($toRefreshUser, $refreshedUser);
    }

    public function testItSupportsUserClassImplementations(): void
    {
        $this->assertTrue($this->subject->supportsClass(User::class));
        $this->assertFalse($this->subject->supportsClass('invalid'));
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

            public function eraseCredentials()
            {
            }
        };
    }

    private function prepareRequestStackMock(int $expectedCalls, string $expectedRoute): void
    {
        $this->requestStack
            ->expects($this->exactly($expectedCalls))
            ->method('getCurrentRequest')
            ->willReturn(new Request([], [], ['_route' => $expectedRoute]));
    }
}
