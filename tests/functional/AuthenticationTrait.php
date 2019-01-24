<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\MockObject\MockBuilder;
use Symfony\Bundle\FrameworkBundle\Client;
use App\Model\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;

trait AuthenticationTrait
{
    /**
     * @param string|string[] $className
     *
     * @return MockBuilder
     */
    abstract protected function getMockBuilder($className);

    protected function logIn(Client $client, ?User $user = null)
    {
        if (!$user) {
            $user = new User('login', 'encoded_password', 'salt');
        }

        $session = $client->getContainer()->get('session');

        $firewallName = 'main';

        $token = new UsernamePasswordToken($user, null, $firewallName, ['ROLE_USER']);
        $session->set('_security_' . $firewallName, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);

        $userProvider = $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userProvider->method('refreshUser')->willReturn($user);

        $client->getContainer()->set('user_provider', $userProvider);
    }
}