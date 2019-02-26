<?php declare(strict_types=1);

namespace App\Tests\Unit\Security\Authenticator;

use App\Security\Authenticator\ApiKeyAuthenticator;
use App\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiKeyAuthenticatorTest extends TestCase
{
    public function testItDoesNotSupportRememberMe(): void
    {
        $subject = new ApiKeyAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            'key'
        );

        $this->assertFalse($subject->supportsRememberMe());
    }

    public function testItThrowsExceptionUnauthorizedExceptionOnAuthenticationError(): void
    {
        $subject = new ApiKeyAuthenticator(
            $this->createMock(AuthorizationHeaderTokenExtractor::class),
            'key'
        );

        /** @var Request|PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('API key authentication failure.');

        $subject->start($request, new AuthenticationException());
    }
}
