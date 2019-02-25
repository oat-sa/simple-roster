<?php declare(strict_types=1);

namespace App\Tests\Unit\Security\Authenticator;

use App\Security\Authenticator\ApiKeyAuthenticator;
use App\Security\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

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
}
