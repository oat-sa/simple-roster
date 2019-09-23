<?php

declare(strict_types=1);

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

namespace App\Security\Provider;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /** @var UserRepository */
    private $userRepository;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(UserRepository $userRepository, RequestStack $requestStack)
    {
        $this->userRepository = $userRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username): UserInterface
    {
        try {
            return $this->userRepository->getByUsernameWithAssignments($username);
        } catch (ORMException $exception) {
            throw new UsernameNotFoundException(sprintf("Username '%s' does not exist", $username));
        }
    }

    /**
     * @throws UnsupportedUserException
     * @throws UsernameNotFoundException
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf("Invalid user class '%s'.", get_class($user)));
        }

        // We dont refresh user on logout since we rely on session storage, so no need to reload it from database
        if ($this->requestStack->getCurrentRequest()->attributes->get('_route') !== 'logout') {
            try {
                return $this->userRepository->getByUsernameWithAssignments($user->getUsername());
            } catch (ORMException $exception) {
                throw new UsernameNotFoundException(sprintf("User '%s' could not be reloaded", $user->getUsername()));
            }
        }

        return $user;
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}
