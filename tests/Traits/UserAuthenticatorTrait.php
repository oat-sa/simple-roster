<?php declare(strict_types=1);

namespace App\Tests\Traits;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait UserAuthenticatorTrait
{
    protected function logInAs(User $user, Client $client, $role = 'ROLE_USER'): void
    {
        /** @var Session $session */
        $session = $client->getContainer()->get('session');

        // the firewall context defaults to the firewall name
        $firewallContext = 'api';

        $token = new UsernamePasswordToken($user, null, $firewallContext, [$role]);
        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}
