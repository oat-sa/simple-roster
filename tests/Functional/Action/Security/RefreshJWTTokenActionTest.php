<?php

namespace OAT\SimpleRoster\Tests\Functional\Action\Security;

use Carbon\Carbon;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use OAT\SimpleRoster\Tests\Traits\UserAuthenticatorTrait;
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
}
