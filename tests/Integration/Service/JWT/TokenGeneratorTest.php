<?php
//
//namespace OAT\SimpleRoster\Tests\Integration\Service\JWT;
//
//use Lcobucci\JWT\Token;
//use OAT\SimpleRoster\Service\JWT\TokenGenerator;
//use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
//use Symfony\Component\Security\Core\User\User;
//
//class TokenGeneratorTest extends KernelTestCase
//{
//    private const IDENTIFIER = 'username';
//
//    /** @var TokenGenerator $subject */
//    private $subject;
//
//    protected function setUp(): void
//    {
//        parent::setUp();
//
//        self::bootKernel();
//
//        $this->subject = self::$container->get(TokenGenerator::class);
//    }
//
//    public function testItCreatesToken(): void
//    {
//        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');
//
//        $user = new User(self::IDENTIFIER, null);
//
//        $token = $this->subject->create($user, $tokenTtl);
//
//        self::assertNotEmpty($token);
//        self::assertInstanceOf(Token::class, $token);
//    }
//}
