<?php declare(strict_types=1);

namespace App\Tests\Unit\Security\OAuth;

use App\Security\OAuth\OAuthContext;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class OAuthContextTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $subject = new OAuthContext(
            'bodyHash',
            'consumerKey',
            'nonce',
            'signatureMethod',
            'timestamp',
            'version'
        );

        $this->assertEquals('bodyHash', $subject->getBodyHash());
        $this->assertEquals('consumerKey', $subject->getConsumerKey());
        $this->assertEquals('nonce', $subject->getNonce());
        $this->assertEquals('signatureMethod', $subject->getSignatureMethod());
        $this->assertEquals('timestamp', $subject->getTimestamp());
        $this->assertEquals('version', $subject->getVersion());
    }
}
