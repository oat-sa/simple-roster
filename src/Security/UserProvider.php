<?php

namespace App\Security;

use App\Model\User;
use App\ModelManager\UserManager;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var UserManager
     */
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $user = $this->userManager->read($username);
        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist', $username));
        }
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(SecurityUserInterface $user)
    {
        $reloadedUser = $this->userManager->read($user->getUsername());

        if (null === $reloadedUser) {
            throw new UsernameNotFoundException(sprintf('User with ID "%s" could not be reloaded', $user->getUsername()));
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}