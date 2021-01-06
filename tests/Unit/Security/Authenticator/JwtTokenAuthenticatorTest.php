<?php

namespace OAT\SimpleRoster\Tests\Unit\Security\Authenticator;

use Lcobucci\JWT\Token;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Authenticator\JwtTokenAuthenticator;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JwtTokenAuthenticatorTest extends TestCase
{
    public function testItDoesNotSupportRememberMe(): void
    {
        $subject = new JwtTokenAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            $this->createMock(JwtTokenVerifier::class),
            $this->createMock(SerializerResponder::class)
        );

        self::assertFalse($subject->supportsRememberMe());
    }


    public function testItExtractsCredentialsCorrectly(): void
    {
        $subject = new JwtTokenAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            $this->createMock(JwtTokenVerifier::class),
            $this->createMock(SerializerResponder::class)
        );

        $extractor = new AuthorizationHeaderTokenExtractor();

        $request = $this->createMock(Request::class);

        $headerBag = $this->getMockBuilder(HeaderBag::class)
            ->setConstructorArgs([
                [
                    'Authorization' => 'Bearer expected'
                ]
            ])
            ->onlyMethods(['has', 'get'])
            ->getMock()
        ;
        $headerBag->method('has')->with('Authorization')->willReturn(true);
        $headerBag->method('get')->with('Authorization')->willReturn('Bearer expected');

        $request->headers = $headerBag;

        $subject->setExtractor($extractor);

        self::assertSame('expected', $subject->getCredentials($request));
    }

    public function testItThrowsExceptionOnNoUsernameClaimInToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token. Unable to parse or no username claim.');

        $subject = new JwtTokenAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            $this->createMock(JwtTokenVerifier::class),
            $this->createMock(SerializerResponder::class)
        );

        $userProviderMock = $this->createMock(UserProviderInterface::class);

        $subject->getUser(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkw' .
            'IiwidXNlciI6InVzZXIxIiwiaWF0IjoxNTE2MjM5MDIyfQ.MqMn8PLjkMH_0pAmkVXg6FolaiaKyZZ_Bqnt-xS50CM',
            $userProviderMock
        );
    }

    public function testItThrowsExceptionOnInvalidToken(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid token.');

        $tokenVerifierMock = $this->createMock(JwtTokenVerifier::class);

        $tokenVerifierMock->expects(self::any())->method('isValid')->willReturn(false);

        $subject = new JwtTokenAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            $tokenVerifierMock,
            $this->createMock(SerializerResponder::class)
        );

        $userProviderMock = $this->createMock(UserProviderInterface::class);

        $subject->getUser(
            'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkw' .
            'IiwidXNlcm5hbWUiOiJKb2huIERvZSIsImlhdCI6MTUxNjIzOTAyMn0.p5Csu2THYW5zJys2CWdbGM8GaWjpY6lOQpdLoP4D7V4',
            $userProviderMock
        );
    }
}
