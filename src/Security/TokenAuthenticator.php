<?php

namespace App\Security;

use App\Model\User;
use App\ModelManager\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session, UserManager $userManager)
    {
        $this->session = $session;
        $this->userManager = $userManager;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     *
     * @inheritdoc
     */
    public function supports(Request $request)
    {
        $session = $request->getSession();

        return $request->getSession()->isStarted() && $session->has('login') && $session->has('password');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     *
     * @inheritdoc
     */
    public function getCredentials(Request $request)
    {
        $session = $request->getSession();

        return array(
            'login' => $session->get('login'),
            'password' => $session->get('password'),
        );
    }

    /**
     * @inheritdoc
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        $login = $credentials['login'];
        $password = $credentials['password'];

        if (null === $login || null === $password) {
            return null;
        }

        /** @var User $user */
        $user = $this->userManager->read($login);
        if ($user && $user->getPassword() === $credentials['password']) {
            return $user;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        );

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     *
     * @inheritdoc
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            // you might translate this message
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @inheritdoc
     */
    public function supportsRememberMe()
    {
        return false;
    }
}