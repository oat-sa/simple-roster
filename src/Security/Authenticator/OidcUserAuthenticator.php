<?php

namespace App\Security\Authenticator;

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityFactoryInterface;

class OidcUserAuthenticator implements UserAuthenticatorInterface
{
    /** UserIdentityFactoryInterface */
    private $factory;

    public function __construct(UserIdentityFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        return new UserAuthenticationResult(
            true,
            $this->factory->create($loginHint, 'Dani', 'dani@tiger.com')
        );
    }
}
