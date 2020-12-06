<?php

namespace OAT\SimpleRoster\Tests\Unit\Security\Authenticator;

use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Security\Authenticator\JwtTokenAuthenticator;
use OAT\SimpleRoster\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use OAT\SimpleRoster\Security\Verifier\JwtTokenVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

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

        $request = $this->getMockBuilder(Request::class)
            ->getMock();
        ;

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
}
