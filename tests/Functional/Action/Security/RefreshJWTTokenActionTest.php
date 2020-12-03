<?php

namespace OAT\SimpleRoster\Tests\Functional\Action\Security;

use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshJWTTokenActionTest extends WebTestCase
{
    use DatabaseTestingTrait;

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
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/login',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertArrayHasKey('accessToken', $decodedResponse);

        self::assertArrayHasKey('refreshToken', $decodedResponse);

        $refreshToken = $decodedResponse['refreshToken'];

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
    }

    public function testItThrows400WithNoToken(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/login',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertArrayHasKey('accessToken', $decodedResponse);

        self::assertArrayHasKey('refreshToken', $decodedResponse);

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
    }

    public function testItThrows409WithIncorrectToken(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/login',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertArrayHasKey('accessToken', $decodedResponse);

        self::assertArrayHasKey('refreshToken', $decodedResponse);

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
    }

    //TODO: override env variable in there
    public function testItThrows401WithExpiredToken(): void
    {
        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/login',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'user1', 'password' => 'password'], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertArrayHasKey('accessToken', $decodedResponse);

        self::assertArrayHasKey('refreshToken', $decodedResponse);

        $this->kernelBrowser->request(
            Request::METHOD_POST,
            '/api/v1/auth/token/refresh',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['refreshToken' => $decodedResponse['refreshToken']], JSON_THROW_ON_ERROR, 512)
        );

        $response = $this->kernelBrowser->getResponse();

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true);

        self::assertSame(
            'Invalid token.',
            $decodedResponse['error']['message']
        );
    }
}
