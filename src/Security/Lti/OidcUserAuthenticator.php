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

namespace OAT\SimpleRoster\Security\Lti;

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Exception\UserNotFoundException;
use OAT\SimpleRoster\Repository\UserRepository;

class OidcUserAuthenticator implements UserAuthenticatorInterface
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['username' => $loginHint]);

        if ($user === null) {
            throw new UserNotFoundException('User not found based on hint: ' . $loginHint);
        }

        return new UserAuthenticationResult(
            true,
            new UserIdentity($user->getUsername())
        );
    }
}
