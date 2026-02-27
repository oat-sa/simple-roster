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
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\Authenticator\JwtTokenAuthenticator;
use OAT\SimpleRoster\Security\Authenticator\JwtConfiguration;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use OAT\SimpleRoster\Security\Provider\UserProvider;
use OAT\SimpleRoster\Tests\AppKernelTestCase;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class JwtTokenAuthenticatorTest extends AppKernelTestCase
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

    #[DataProvider('provideUnsupportedAuthorizationHeaderPayload')]
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

    public function testItCanAuthenticateRequestWithBearerToken(): void
    {
        $token = $this->tokenGenerator->create(
            (new User())->setUsername('user1'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()]
        );

        $passport = $this->subject->authenticate($request);

        self::assertInstanceOf(Passport::class, $passport);
        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertSame('user1', $userBadge->getUserIdentifier());
    }

    public function testItThrowsAuthenticationExceptionIfTokenCannotBeParsed(): void
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalidToken']
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');

        $this->subject->authenticate($request);
    }

    public function testOnAuthenticationFailureReturnsCorrectResponse(): void
    {
        $exception = new CustomUserMessageAuthenticationException('Invalid token.');

        $response = $this->subject->onAuthenticationFailure(
            Request::create('/test'),
            $exception
        );

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $decodedContent = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Invalid token.', $decodedContent);
    }

    public function testItThrowsAuthenticationExceptionIfTokenIsNotValid(): void
    {
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer aaa.bbb.ccc']
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');

        $this->subject->authenticate($request);
    }

    public function testItThrowsAuthenticationExceptionIfAudClaimIsNotPresent(): void
    {
        $jwtConfigInitialize = $this->jwtConfig->initialise();
        $token = $jwtConfigInitialize->builder()
            ->relatedTo('accessToken')
            ->getToken($jwtConfigInitialize->signer(), $jwtConfigInitialize->signingKey());

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()]
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');

        $this->subject->authenticate($request);
    }

    public function testItThrowsAuthenticationExceptionIfSubjectIsNotAccessToken(): void
    {
        $jwtConfigInitialize = $this->jwtConfig->initialise();
        $token = $jwtConfigInitialize->builder()
            ->permittedFor('testAudience')
            ->getToken($jwtConfigInitialize->signer(), $jwtConfigInitialize->signingKey());

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()]
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');

        $this->subject->authenticate($request);
    }

    public function testItThrowsAuthenticationExceptionIfTokenIsExpired(): void
    {
        $expiredToken = $this->tokenGenerator->create(
            (new User())->setUsername('testUser'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        Carbon::setTestNow('+2 hours');

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $expiredToken->toString()]
        );

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Expired token.');

        $this->subject->authenticate($request);

        Carbon::setTestNow();
    }

    public function testItReturnsForbiddenStatusOnAuthenticationFailure(): void
    {
        $exception = new CustomUserMessageAuthenticationException('Expired token.');
        $response = $this->subject->onAuthenticationFailure(Request::create('/test'), $exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Expired token.', $content);
    }

    public function testItThrowsExceptionIfUserCannotBeFound(): void
    {
        $token = $this->tokenGenerator->create(
            (new User())->setUsername('testUser'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        $request = Request::create('/test', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()
        ]);

        $passport = $this->subject->authenticate($request);

        $userBadge = $passport->getBadge(UserBadge::class);
        $userBadge->setUserLoader(fn() => $this->userProvider->loadUserByIdentifier('testUser'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage("Username 'testUser' does not exist");

        $passport->getUser();
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

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()]
        );

        $passport = $this->subject->authenticate($request);

        self::assertInstanceOf(Passport::class, $passport);

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $userBadge);
        self::assertTrue($passport->hasBadge(UserBadge::class));
        self::assertSame('user1', $userBadge->getUserIdentifier());
    }

    public function testItReturnsJsonResponseOnAuthenticationFailure(): void
    {
        $message = 'Expected exception message';
        $exception = new AuthenticationException($message);
        $request = Request::create('/test');

        $response = $this->subject->onAuthenticationFailure($request, $exception);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $decodedResponse = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($message, $decodedResponse);
    }

    public function testItDoesNotSupportRememberMe(): void
    {
        $token = $this->tokenGenerator->create(
            (new User())->setUsername('user1'),
            Request::create('/test'),
            'accessToken',
            3600
        );

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()]
        );

        $passport = $this->subject->authenticate($request);

        self::assertFalse($passport->hasBadge(RememberMeBadge::class));
    }

    public static function provideUnsupportedAuthorizationHeaderPayload(): array
    {
        return [
            'nonBearer' => ['nonBearer Something'],
            'empty' => [''],
            'OAuth realm' => ['Oauth realm'],
            'BearerWithEmptyToken' => ['Bearer '],
        ];
    }
}
