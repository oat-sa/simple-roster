<?php declare(strict_types=1);

namespace App\Security;

use App\Model\User;
use App\ODM\ItemManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private $itemManager;

    public function __construct(ItemManagerInterface $itemManager)
    {
        $this->itemManager = $itemManager;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * @return UserInterface
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username): UserInterface
    {
        /** @var User $user */
        $user = $this->itemManager->load(User::class, $username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist', $username));
        }

        return $user;
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        /** @var User $reloadedUser */
        $reloadedUser = $this->itemManager->load(User::class, $user->getUsername());

        if (null === $reloadedUser) {
            throw new UsernameNotFoundException(sprintf('User "%s" could not be reloaded', $user->getUsername()));
        }

        return $reloadedUser;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}
