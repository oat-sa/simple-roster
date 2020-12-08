<?php

namespace OAT\SimpleRoster\Tests\Integration\Service\JWT;

use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Service\JWT\JWTManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\User;

class JWTManagerTest extends KernelTestCase
{
    private const IDENTIFIER = 'username';

    /** @var JWTManager $subject */
    private $subject;

    /** @var CacheItemPoolInterface $cache */
    private $cache;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->subject = self::$container->get(JWTManager::class);
        $this->cache = self::$container->get(CacheItemPoolInterface::class);
    }

    public function testItCreatesToken(): void
    {
        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User(self::IDENTIFIER, null);

        $token = $this->subject->create($user, $tokenTtl);

        self::assertNotEmpty($token);
        self::assertInstanceOf(Token::class, $token);
    }

    public function testGenerateCacheId(): void
    {
        $generatedId = $this->subject->generateCacheId(self::IDENTIFIER);

        self::assertNotEmpty($generatedId);
        self::assertEquals(("jwt-token." . self::IDENTIFIER), $generatedId);
    }

    public function testItStoresTokenInCache(): void
    {
        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User(self::IDENTIFIER, null);

        $token = $this->subject->create($user, $tokenTtl);

        self::assertNotEmpty($token);
        self::assertInstanceOf(Token::class, $token);

        $this->subject->storeTokenInCache($token, $tokenTtl);

        $generatedId = $this->subject->generateCacheId(self::IDENTIFIER);

        self::assertNotEmpty($generatedId);
        self::assertEquals(("jwt-token." . self::IDENTIFIER), $generatedId);

        self::assertTrue($this->cache->hasItem($generatedId));
    }

    public function testGetStoredToken(): void
    {
        $tokenTtl = self::$container->getParameter('app.jwt.access_token_ttl');

        $user = new User(self::IDENTIFIER, null);

        $token = $this->subject->create($user, $tokenTtl);

        self::assertNotEmpty($token);
        self::assertInstanceOf(Token::class, $token);

        $this->subject->storeTokenInCache($token, $tokenTtl);

        $generatedId = $this->subject->generateCacheId(self::IDENTIFIER);

        self::assertNotEmpty($generatedId);
        self::assertEquals(("jwt-token." . self::IDENTIFIER), $generatedId);

        self::assertTrue($this->cache->hasItem($generatedId));
        self::assertEquals((string)$token, $this->cache->getItem($generatedId)->get());
    }
}
