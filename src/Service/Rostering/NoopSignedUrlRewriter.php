<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

class NoopSignedUrlRewriter implements SignedUrlRewriterInterface
{
    public function rewrite(string $signedUrl): string
    {
        return $signedUrl;
    }
}

