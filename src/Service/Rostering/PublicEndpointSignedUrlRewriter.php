<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

class PublicEndpointSignedUrlRewriter implements SignedUrlRewriterInterface
{
    public function __construct(private readonly string $publicEndpoint = '')
    {
    }

    public function rewrite(string $signedUrl): string
    {
        $publicEndpoint = trim($this->publicEndpoint);
        if ($publicEndpoint === '') {
            return $signedUrl;
        }

        $publicParts = parse_url($publicEndpoint);
        $signedUrlParts = parse_url($signedUrl);
        if (
            $publicParts === false
            || $signedUrlParts === false
            || !isset($publicParts['scheme'], $publicParts['host'])
        ) {
            return $signedUrl;
        }

        $host = sprintf(
            '%s://%s%s',
            $publicParts['scheme'],
            $publicParts['host'],
            isset($publicParts['port']) ? sprintf(':%d', (int) $publicParts['port']) : ''
        );

        return sprintf(
            '%s%s%s',
            $host,
            $signedUrlParts['path'] ?? '',
            isset($signedUrlParts['query']) ? sprintf('?%s', $signedUrlParts['query']) : ''
        );
    }
}

