<?php

/*
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Integration\Security\Authenticator;

use Carbon\Carbon;
use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\Authenticator\JwtTokenAuthenticator;
use OAT\SimpleRoster\Security\Authenticator\JwtConfiguration;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use OAT\SimpleRoster\Security\Provider\UserProvider;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class JwtTokenAuthenticatorTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    private JwtTokenAuthenticator $subject;
    private JwtTokenGenerator $tokenGenerator;
    private UserProvider $userProvider;
    private JwtConfiguration $jwtConfig;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = self::getContainer()->get(JwtTokenAuthenticator::class);

        $this->tokenGenerator = self::getContainer()->get(JwtTokenGenerator::class);
        $this->userProvider = self::getContainer()->get(UserProvider::class);
        $this->jwtConfig = self::getContainer()->get(JwtConfiguration::class);
    }

    public function testItSupportRequestOnlyWithAuthorizationBearerHeaderPresent(): void
    {
        self::assertFalse($this->subject->supports(Request::create('/test')));

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer testToken']
        );

        self::assertTrue($this->subject->supports($request));
    }


    /**
     * @dataProvider provideUnsupportedAuthorizationHeaderPayload
     */
    public function testItDoesNotSupportRequestWithAuthorizationHeaderButNoBearerPayloadPrefix(
        string $authorizationHeaderPayload
    ): void {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => $authorizationHeaderPayload]
        );

        self::assertFalse($this->subject->supports($request));
    }

    public function testItCanReturnCredentialsFromRequest(): void
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer expectedCredentials']
        );

        self::assertSame('expectedCredentials', $this->subject->getCredentials($request));
    }

    public function testItThrowsAuthenticationExceptionIfTokenCannotBeParsed(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $this->subject->getUser('invalidToken', $this->userProvider);
    }

    public function testItThrowsAuthenticationExceptionIfTokenIsNotValid(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $this->subject->getUser($this->createMock(Token::class), $this->userProvider);
    }

    public function testItThrowsAuthenticationExceptionIfAudClaimIsNotPresent(): void
    {
        $jwtConfigInitialize = $this->jwtConfig->initialise();
        $builder = $jwtConfigInitialize->builder();
        $token = $builder->getToken($jwtConfigInitialize->signer(), $jwtConfigInitialize->signingKey());

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $this->subject->getUser($token->toString(), $this->userProvider);
    }

    public function testItThrowsAuthenticationExceptionIfSubjectIsNotAccessToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $jwtConfigInitialize = $this->jwtConfig->initialise();
        $token = $jwtConfigInitialize->builder()
            ->permittedFor('testAudience')
            ->getToken($jwtConfigInitialize->signer(), $jwtConfigInitialize->signingKey());

        $this->subject->getUser($token->toString(), $this->userProvider);
    }

    public function testItThrowsAuthenticationExceptionIfTokenIsExpired(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Expired token.');
        $this->expectExceptionCode(Response::HTTP_FORBIDDEN);

        $expiredToken = $this->tokenGenerator->create(
            (new User())->setUsername('testUser'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        Carbon::setTestNow('+2 hours');

        $this->subject->getUser($expiredToken->toString(), $this->userProvider);

        Carbon::setTestNow();
    }

    public function testItThrowsExceptionIfUserCannotBeFound(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Username 'testUser' does not exist");

        $token = $this->tokenGenerator->create(
            (new User())->setUsername('testUser'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        $this->subject->getUser($token->toString(), $this->userProvider);
    }

    public function testItCanSuccessfullyReturnUser(): void
    {
        $this->loadFixtureByFilename('userWithReadyAssignment.yml');

        $token = $this->tokenGenerator->create(
            (new User())->setUsername('user1'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        $user = $this->subject->getUser($token->toString(), $this->userProvider);

        self::assertSame('user1', $user->getUsername());
    }

    public function testItBubblesUpExceptionOnAuthenticationFailure(): void
    {
        $expectedException = new AuthenticationException('Expected exception');
        $this->expectExceptionObject($expectedException);

        $this->subject->onAuthenticationFailure(Request::create('/test'), $expectedException);
    }

    public function testItDoesNotSupportRememberMe(): void
    {
        self::assertFalse($this->subject->supportsRememberMe());
    }

    public function provideUnsupportedAuthorizationHeaderPayload(): array
    {
        return [
            'nonBearer' => ['nonBearer Something'],
            'empty' => [''],
            'OAuth realm' => ['Oauth realm'],
            'BearerWithEmptyToken' => ['Bearer '],
        ];
    }
}
