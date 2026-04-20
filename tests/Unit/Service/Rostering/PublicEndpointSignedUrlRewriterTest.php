<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\PublicEndpointSignedUrlRewriter;
use PHPUnit\Framework\TestCase;

class PublicEndpointSignedUrlRewriterTest extends TestCase
{
    public function testItKeepsSignedUrlWhenPublicEndpointIsEmpty(): void
    {
        $subject = new PublicEndpointSignedUrlRewriter('');
        $signedUrl = 'http://dd-localstack:4566/rostering-files/a.csv?X-Amz-Signature=abc123';

        self::assertSame($signedUrl, $subject->rewrite($signedUrl));
    }

    public function testItKeepsAwsSignedUrlUnchangedWhenPublicEndpointIsEmpty(): void
    {
        $subject = new PublicEndpointSignedUrlRewriter('');
        $signedUrl = 'https://frdep23sre-s3stack-10begihvqzo7v-privatebucket-igeahj0biagq.s3.eu-west-3.amazonaws.com/uploads/0d68c8ed-1e58-4157-97cb-04684573088c/output.csv?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Expires=900&X-Amz-Signature=de7c417c42427e0a37378f03b3b9e0d768b21c01d5143d399d07f928f7bc33ab';

        self::assertSame($signedUrl, $subject->rewrite($signedUrl));
    }

    public function testItReplacesHostWithConfiguredPublicEndpoint(): void
    {
        $subject = new PublicEndpointSignedUrlRewriter('http://localhost:4566');
        $signedUrl = 'http://dd-localstack:4566/rostering-files/a.csv?X-Amz-Signature=abc123';

        self::assertSame(
            'http://localhost:4566/rostering-files/a.csv?X-Amz-Signature=abc123',
            $subject->rewrite($signedUrl)
        );
    }

    public function testItKeepsSignedUrlWhenPublicEndpointIsInvalid(): void
    {
        $subject = new PublicEndpointSignedUrlRewriter('localhost:4566');
        $signedUrl = 'http://dd-localstack:4566/rostering-files/a.csv?X-Amz-Signature=abc123';

        self::assertSame($signedUrl, $subject->rewrite($signedUrl));
    }
}
