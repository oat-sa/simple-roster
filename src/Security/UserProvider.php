<?php declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityNotFoundException;
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
     * @throws EntityNotFoundException
     */
    public function loadUserByUsername($username): UserInterface
    {
        /** @var User $user */
        $user = $this->userRepository->getByUsernameWithAssignments($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist', $username));
        }

        return $user;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        /** @var User $reloadedUser */
        $reloadedUser = $this->userRepository->getByUsernameWithAssignments($user->getUsername());

        if (null === $reloadedUser) {
            throw new UsernameNotFoundException(sprintf('User "%s" could not be reloaded', $user->getUsername()));
        }

        return $reloadedUser;
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}
