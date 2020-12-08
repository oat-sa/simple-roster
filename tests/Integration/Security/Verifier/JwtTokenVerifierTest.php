<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Security\Verifier;

use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use OAT\SimpleRoster\Service\JWT\JWTManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;

class JwtTokenVerifierTest extends KernelTestCase
{
    public function testItThrowsExceptionOnInvalidToken(): void
    {
        self::bootKernel();

        /** @var JWTManager $tokenManager */
        $tokenManager = self::$container->get(JWTManager::class);

        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User('username', null);

        $tokenManager->create($user, $tokenTtl);

        $subject = new JwtTokenVerifier('file://' . __DIR__ . '/../../../../config/secrets/test/jwt_public.pem');

        $this->expectException(\BadMethodCallException::class);

        $subject->isValid(new Token(['alg' => 'invalidAlg']));
    }

    public function testItCanVerifyToken(): void
    {
        self::bootKernel();

        /** @var JWTManager $tokenManager */
        $tokenManager = self::$container->get(JWTManager::class);

        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User('username', null);

        $token = $tokenManager->create($user, $tokenTtl);

        $subject = new JwtTokenVerifier('file://' . __DIR__ . '/../../../../config/secrets/test/jwt_public.pem');

        $this->assertTrue($subject->isValid($token));
    }
}
