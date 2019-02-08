<?php declare(strict_types=1);

namespace App\Tests\Integration\Security\OAuth;

use App\Security\OAuth\OAuthContext;
use App\Security\OAuth\OAuthSigner;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class OAuthSignerTest extends TestCase
{
    private const TIMESTAMP = '1549615519';

    public function testSignWithMacSha1Method(): void
    {
        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext(OAuthContext::METHOD_MAC_SHA1);

        $this->assertEquals(
            'VoEu7pwaoCuBG5+59qp1WcHhq/o=',
            $subject->sign($context, 'url', 'method', 'secret')
        );
    }

    public function testSignWithMacSha1MethodAndAdditionalParameters(): void
    {
        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext(OAuthContext::METHOD_MAC_SHA1);

        $this->assertEquals(
            'Mtx4QCOoeGw3rgUXfn9bWxl8Rhw=',
            $subject->sign($context, 'url', 'method', 'secret', ['param1', 'param2'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Signature method 'invalid' is not supported
     */
    public function testSignWithInvalidMethod(): void
    {
        $subject = new OAuthSigner();

        $context = $this->generateOAuthContext('invalid');

        $subject->sign($context, 'url', 'method', 'secret');
    }

    private function generateOAuthContext(string $signatureMethod): OAuthContext
    {
        return new OAuthContext(
            'bodyHash',
            'consumerKey',
            'nonce',
            $signatureMethod,
            self::TIMESTAMP,
            OAuthContext::VERSION_1_0
        );
    }
}
