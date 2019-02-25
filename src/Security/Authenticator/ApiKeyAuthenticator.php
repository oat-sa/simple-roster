<?php declare(strict_types=1);

namespace App\Security\Authenticator;

use App\Entity\User;
use App\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiKeyAuthenticator extends AbstractGuardAuthenticator
{
    public const AUTH_REALM = 'SimpleRoster';

    /** @var AuthorizationHeaderTokenExtractor */
    private $tokenExtractor;

    /** @var string */
    private $appApiKey;

    public function __construct(AuthorizationHeaderTokenExtractor $tokenExtractor, string $appApiKey)
    {
        $this->tokenExtractor = $tokenExtractor;
        $this->appApiKey = $appApiKey;
    }

    public function supports(Request $request)
    {
        return $request->headers->has(AuthorizationHeaderTokenExtractor::AUTHORIZATION_HEADER);
    }

    public function getCredentials(Request $request)
    {
        return [
            'token' => $this->tokenExtractor->extract($request),
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return new User();
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $credentials['token'] === $this->appApiKey;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw $this->createUnauthorizedHttpException($exception);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw $this->createUnauthorizedHttpException($authException);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    private function createUnauthorizedHttpException(?AuthenticationException $exception): UnauthorizedHttpException
    {
        $message = 'API key authentication failure.';

        return new UnauthorizedHttpException(
            sprintf('Bearer realm="%s", error="invalid_api_key", error_description="%s"', static::AUTH_REALM, $message),
            $message,
            $exception
        );
    }
}