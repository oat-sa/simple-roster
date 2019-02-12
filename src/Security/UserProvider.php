<?php declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\ORMException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username): UserInterface
    {
        try {
            return $this->userRepository->getByUsernameWithAssignments($username);
        } catch (ORMException $exception) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist', $username));
        }
    }

    /**
     * @throws UnsupportedUserException
     * @throws UsernameNotFoundException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        try {
            return $this->userRepository->getByUsernameWithAssignments($user->getUsername());
        } catch (ORMException $exception) {
            throw new UsernameNotFoundException(sprintf('User "%s" could not be reloaded', $user->getUsername()));
        }
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}
