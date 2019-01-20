<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class LoginManager implements LoginManagerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;

    /**
     * @var GuardAuthenticatorHandler
     */
    private $guardHandler;

    public function __construct(TokenStorageInterface $tokenStorage, EncoderFactoryInterface $encoderFactory, GuardAuthenticatorHandler $guardHandler)
    {
        $this->tokenStorage = $tokenStorage;
        $this->encoderFactory = $encoderFactory;
        $this->guardHandler = $guardHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function logInUser(Request $request, $firewallName, UserInterface $user): bool
    {
        $encodedRequestPassword = $this->encoderFactory->getEncoder($user)->encodePassword($request->request->get('password'), $user->getSalt());

        if ($user->getPassword() === $encodedRequestPassword) {
            $token = new UsernamePasswordToken($user, null, $firewallName, $user->getRoles());

            $this->guardHandler->authenticateWithToken($token, $request);
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function logOutUser(Request $request): void
    {
        $request->getSession()->invalidate();
        $this->tokenStorage->setToken(null);
    }
}