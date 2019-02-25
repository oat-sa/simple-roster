<?php declare(strict_types=1);

namespace App\Tests\Integration\Security\Provider;

use App\Entity\User;
use App\Security\Provider\UserProvider;
use App\Tests\Traits\DatabaseFixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends KernelTestCase
{
    use DatabaseFixturesTrait;

    /** @var UserProvider */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();

        $this->subject = new UserProvider($this->getRepository(User::class));
    }

    public function testItThrowsUsernameNotFoundExceptionWhenLoadingUserWithInvalidUser(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('Username "invalid" does not exist');

        $this->subject->loadUserByUsername('invalid');
    }

    public function testItThrowsUnsupportedUserExceptionWhenRefreshingInvalidUserImplementation(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Invalid user class');

        $this->subject->refreshUser($this->createNonSupportedUserInterfaceImplementation());
    }

    public function testItThrowsUsernameNotFoundExceptionWhenRefreshingInvalidUser(): void
    {
        $this->expectException(UsernameNotFoundException::class);
        $this->expectExceptionMessage('User "invalid" could not be reloaded');

        $this->subject->refreshUser((new User())->setUsername('invalid'));
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
                return;
            }
        };
    }
}
