<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Security\Verifier;

use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use OAT\SimpleRoster\Service\JWT\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;

class JwtTokenVerifierTest extends KernelTestCase
{
    public function testItThrowsExceptionOnInvalidToken(): void
    {
        self::bootKernel();

        /** @var TokenGenerator $tokenGenerator */
        $tokenGenerator = self::$container->get(TokenGenerator::class);

        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User('username', null);

        $tokenGenerator->create($user, $tokenTtl);

        $subject = new JwtTokenVerifier(self::$container->getParameter('app.jwt.public_key_path'));

        $this->expectException(\BadMethodCallException::class);

        $subject->isValid(new Token(['alg' => 'invalidAlg']));
    }

    public function testItCanVerifyToken(): void
    {
        self::bootKernel();

        /** @var TokenGenerator $tokenGenerator */
        $tokenGenerator = self::$container->get(TokenGenerator::class);

        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User('username', null);

        $token = $tokenGenerator->create($user, $tokenTtl);

        $subject = new JwtTokenVerifier(self::$container->getParameter('app.jwt.public_key_path'));

        $this->assertTrue($subject->isValid($token));
    }
}
