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
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Security\Authenticator\JwtTokenAuthenticator;
use OAT\SimpleRoster\Security\Generator\JwtTokenGenerator;
use OAT\SimpleRoster\Tests\Traits\DatabaseTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class JwtTokenAuthenticatorTest extends KernelTestCase
{
    use DatabaseTestingTrait;

    private JwtTokenAuthenticator $subject;
    private JwtTokenGenerator $tokenGenerator;
    private string $jwtPrivateKeyPath;
    private string $jwtPassphrase;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->setUpDatabase();

        $this->subject = self::getContainer()->get(JwtTokenAuthenticator::class);

        $this->tokenGenerator = self::getContainer()->get(JwtTokenGenerator::class);
        $this->jwtPrivateKeyPath = self::getContainer()->getParameter('app.jwt.private_key_path');
        $this->jwtPassphrase = self::getContainer()->getParameter('app.jwt.passphrase');
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

    public function testItThrowsAuthenticationExceptionIfTokenCannotBeParsed(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalidToken'],
        );

        $this->subject->authenticate($request);
    }

    public function testItThrowsAuthenticationExceptionIfTokenIsNotValid(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        // Signer should be Sha256() to get a valid token
        $token = (new Builder())->getToken(new Sha512(), new Key($this->jwtPrivateKeyPath, $this->jwtPassphrase));

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()],
        );

        $this->subject->authenticate($request);
    }

    public function testItThrowsAuthenticationExceptionIfAudClaimIsNotPresent(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $token = (new Builder())->getToken(new Sha256(), new Key($this->jwtPrivateKeyPath, $this->jwtPassphrase));

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()],
        );

        $this->subject->authenticate($request);
    }

    public function testItThrowsAuthenticationExceptionIfSubjectIsNotAccessToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');
        $this->expectExceptionCode(Response::HTTP_BAD_REQUEST);

        $token = (new Builder())
            ->permittedFor('testAudience')
            ->getToken(new Sha256(), new Key($this->jwtPrivateKeyPath, $this->jwtPassphrase));

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()],
        );

        $this->subject->authenticate($request);
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

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $expiredToken->toString()],
        );

        Carbon::setTestNow('+2 hours');

        $this->subject->authenticate($request);

        Carbon::setTestNow();
    }

    public function testItCanSuccessfullyAuthenticate(): void
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
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token->toString()],
        );

        $passport = $this->subject->authenticate($request);

        self::assertTrue($passport->hasBadge(UserBadge::class));

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        self::assertSame('user1', $userBadge->getUserIdentifier());
    }

    public function testItBubblesUpExceptionOnAuthenticationFailure(): void
    {
        $expectedException = new AuthenticationException('Expected exception');
        $this->expectExceptionObject($expectedException);

        $this->subject->onAuthenticationFailure(Request::create('/test'), $expectedException);
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
