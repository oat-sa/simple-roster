<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Security\Verifier;

use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use OAT\SimpleRoster\Service\JWT\JWTManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;

class JwtTokenVerifierTest extends KernelTestCase
{
    public function testItCanVerifyToken(): void
    {
        self::bootKernel();

        /** @var JWTManager $tokenManager */
        $tokenManager = self::$container->get(JWTManager::class);

        $user = new User('username', null);

        $token = $tokenManager->create($user);

        $subject = new JwtTokenVerifier('file://' . __DIR__ . '/../../../../config/secrets/test/jwt_public.pem');

        $this->assertTrue($subject->isValid($token));
    }
}
