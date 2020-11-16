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

class OidcUserAuthenticator implements UserAuthenticatorInterface
{
    public const UNDEFINED_USERNAME = 'Undefined Username';

    /** @var LoginHintValidator */
    private $loginHintValidator;

    public function __construct(LoginHintValidator $loginHintValidator)
    {
        $this->loginHintValidator = $loginHintValidator;
    }

    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        $user = $this->loginHintValidator->validate($loginHint);

        return new UserAuthenticationResult(
            true,
            new UserIdentity($user->getUsername() ?? self::UNDEFINED_USERNAME)
        );
    }
}
