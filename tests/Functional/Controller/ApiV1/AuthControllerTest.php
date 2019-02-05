<?php declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\BaseWebTestCase;

class AuthControllerTest extends BaseWebTestCase
{
    private const ENDPOINT_LOGIN = '/api/v1/auth/login';
    private const ENDPOINT_LOGOUT = '/api/v1/auth/logout';

    /**
     * @var string
     */
    private $plainPassword = ' qWeR_y123.{]}@.2 _,      ';

    public function getUrlsForMethodNotAllowed()
    {
        return [
            ['GET', self::ENDPOINT_LOGIN],
            ['GET', self::ENDPOINT_LOGOUT]
        ];
    }

    public function testInvalidJsonForLogin()
    {
        $this->postJson(self::ENDPOINT_LOGIN, '{invalid":"json}');

        $this->assertEquals(400, $this->getClient()->getResponse()->getStatusCode());

        $this->assertApiProblemResponse(400, 'Bad Request', 'Invalid JSON.');
    }

    public function getWrongJsonKeys()
    {
        return [
            'wrong_username_key' => [
                ['wrong_username_key', 'password'], 'The key "username" must be provided.'
            ],
            'empty_username_key' => [
                ['', 'password'], 'The key "username" must be provided.'
            ],
            'wrong_pwd_key' => [
                ['username', 'wrong_pwd_key'], 'The key "password" must be provided.'
            ],
            'empty_pwd_key' => [
                ['username', ''], 'The key "password" must be provided.'
            ]
        ];
    }

    /**
     * @dataProvider getWrongJsonKeys
     */
    public function testInvalidJsonStructureForLogin(array $keys, string $expectedDetail)
    {
        $credentialsJson = '{
            "'. $keys[0] .'": "does_not_matter",
            "'. $keys[1] .'": "does_not_matter"
        }';

        $this->postJson(self::ENDPOINT_LOGIN, $credentialsJson);

        $this->assertEquals(400, $this->getClient()->getResponse()->getStatusCode());

        $this->assertApiProblemResponse(400, 'Bad Request', $expectedDetail);
    }

    public function getWrongCredentials()
    {
        return [
            'wrong_username' => [
                ['fakeUser', 'pwd'], 'Bad credentials.'
            ],
            'existing_user_wrong_pwd' => [
                ['user_1', 'fakePWD'], 'Bad credentials.'
            ],
            'existing_user_upper_pwd' => [
                ['user_1', strtoupper($this->plainPassword)], 'Bad credentials.'
            ],
            'existing_user_lower_pwd' => [
                ['user_1', strtolower($this->plainPassword)], 'Bad credentials.'
            ],
            'empty_values' => [
                ['', ''], 'Bad credentials.'
            ]
        ];
    }

    /**
     * @dataProvider getWrongCredentials
     */
    public function testInvalidCredentialsForLogin(array $credentials, string $expectedDetail)
    {
        // user_1 should exist
        if ('user_1' === $credentials[0]) {
            $this->addUser('user_1', $this->plainPassword);
        }

        $credentialsJson = '{
            "username": "'. $credentials[0] .'",
            "password": "'. $credentials[1] .'"
        }';

        $this->postJson(self::ENDPOINT_LOGIN, $credentialsJson);

        $this->assertEquals(403, $this->getClient()->getResponse()->getStatusCode());

        $this->assertApiProblemResponse(403, 'Forbidden', $expectedDetail);
    }

    public function testSuccessfulLogin(): void
    {
        $username = 'user_1';

        $this->addUser($username, $this->plainPassword);

        $credentialsJson = '{
            "username": "'. $username .'",
            "password": "'. $this->plainPassword .'"
        }';

        $this->postJson(self::ENDPOINT_LOGIN, $credentialsJson);

        $this->assertEquals(204, $this->getClient()->getResponse()->getStatusCode());
        $this->assertTrue($this->getClient()->getResponse()->headers->has('Set-Cookie'), 'Session cookie does not exist in response');
        $this->assertEmpty($this->getClient()->getResponse()->getContent());
    }

    public function testLogoutIsSecured()
    {
        $this->postJson(self::ENDPOINT_LOGOUT, '');

        $this->assertEquals(401, $this->getClient()->getResponse()->getStatusCode());

        $this->assertApiProblemResponse(401, 'Unauthorized', 'Full authentication is required to access this resource.');
    }

    public function testLogoutSuccessfulyAfterLogin()
    {
        $user = $this->addUser('user_1', $this->plainPassword);

        $this->logInAs($user);

        $this->postJson(self::ENDPOINT_LOGOUT, '');
        $this->assertEquals(204, $this->getClient()->getResponse()->getStatusCode());

        // second logout request should fail
        $this->postJson(self::ENDPOINT_LOGOUT, '');
        $this->assertEquals(401, $this->getClient()->getResponse()->getStatusCode());
    }
}