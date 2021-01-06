<?php

namespace OAT\SimpleRoster\Tests\Functional\Action\Security;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\JWT\TokenGenerator;
use OAT\SimpleRoster\Service\JWT\TokenStorage;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\UserAuthenticatorTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshAccessTokenActionTest extends WebTestCase
{
    use DatabaseTestingTrait;
    use UserAuthenticatorTrait;

    /** @var KernelBrowser */
    private $kernelBrowser;

    protected function setUp(): void
    {
        $this->kernelBrowser = self::createClient();

        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        parent::setUp();
    }

    public function testItReturnsRefreshTokenAndItCanBeUsed(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => $refreshToken], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertArrayHasKey('accessToken', $decodedResponse);

        Carbon::setTestNow();
    }

    public function testItStoresRefreshTokensInCache(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $cachePool = static::$container->get(TokenStorage::class);

        $this->assertSame($refreshToken, $cachePool->getStoredToken('user1')->get());

        Carbon::setTestNow();
    }

    public function testItThrows400WithNoToken(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            ''
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertArrayNotHasKey('accessToken', $decodedResponse);

        self::assertSame(
            'Missing \'refreshToken\' in request body.',
            $decodedResponse['error']['message']
        );

        Carbon::setTestNow();
    }

    public function testItThrows409WithIncorrectToken(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => 'incorrectOne'], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertSame(
            'Invalid token.',
            $decodedResponse['error']['message']
        );

        Carbon::setTestNow();
    }

    public function testItThrows409WithNoUserClaimInToken(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $tokenObject = (new Parser())->parse($refreshToken);

        /** @var TokenGenerator $generator */
        $generator = self::$container->get(TokenGenerator::class);

        $neededClaims = [];
        foreach ($tokenObject->getClaims() as $header => $claim) {
            if ($header !== 'username') {
                $neededClaims[$header] = $claim;
            }
        }

        $fakeToken = $generator->generateJWTString($neededClaims);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => (string)$fakeToken], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertSame(
            'Invalid token. User claim is missing',
            $decodedResponse['error']['message']
        );

        Carbon::setTestNow();
    }

    public function testItThrows409WithNotExistentUser(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $tokenObject = (new Parser())->parse($refreshToken);

        /** @var TokenGenerator $generator */
        $generator = self::$container->get(TokenGenerator::class);

        $neededClaims = [];
        foreach ($tokenObject->getClaims() as $header => $claim) {
            if (!in_array($header, ['username', 'jti'])) {
                $neededClaims[$header] = $claim;
            }
            $neededClaims[$header] = 'fakeUsername';
        }

        $fakeToken = $generator->generateJWTString($neededClaims);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => (string)$fakeToken], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertSame(
            'Invalid token. User not found',
            $decodedResponse['error']['message']
        );

        Carbon::setTestNow();
    }

    public function testItThrows401WithExpiredToken(): void
    {
        Carbon::setTestNow(Carbon::create(2019, 1, 1, 0, 0, 0));

        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        Carbon::setTestNow();

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => $refreshToken], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertSame(
            'Expired token.',
            $decodedResponse['error']['message']
        );
    }

    public function testItThrows409WithMissingToken(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getRepository(User::class);
        $user = $userRepository->findByUsernameWithAssignments('user1');

        $refreshToken = $this->logInAs($user, $this->kernelBrowser);

        self::assertNotEmpty($this->kernelBrowser->getServerParameter('HTTP_Authorization'));
        self::assertNotNull($refreshToken);

        $cachePool = static::$container->get(TokenStorage::class);
        $cachePool->removeStoredToken('user1');

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => $refreshToken], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertSame(
            'Refresh token is incorrect.',
            $decodedResponse['error']['message']
        );
    }
}
