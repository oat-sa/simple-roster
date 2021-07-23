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

namespace OAT\SimpleRoster\Security\Provider;

use Doctrine\ORM\ORMException;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    private UserRepository $userRepository;
    private RequestStack $requestStack;

    public function __construct(UserRepository $userRepository, RequestStack $requestStack)
    {
        $this->userRepository = $userRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws UserNotFoundException
     */
    public function loadUserByUsername(string $username): UserInterface
    {
        try {
            return $this->userRepository->findByUsernameWithAssignments($username);
        } catch (ORMException $exception) {
            throw new UserNotFoundException(sprintf("Username '%s' does not exist", $username));
        }
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    /**
     * @throws UnsupportedUserException
     * @throws UserNotFoundException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf("Invalid user class '%s'.", get_class($user)));
        }

        // We dont refresh user on logout since we rely on session storage, so no need to reload it from database
        if ($this->requestStack->getCurrentRequest()->attributes->get('_route') !== 'logout') {
            try {
                return $this->userRepository->findByUsernameWithAssignments((string)$user->getUsername());
            } catch (ORMException $exception) {
                throw new UserNotFoundException(sprintf("User '%s' could not be reloaded", $user->getUsername()));
            }
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }
}
