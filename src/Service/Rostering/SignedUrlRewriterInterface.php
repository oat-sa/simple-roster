<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

interface SignedUrlRewriterInterface
{
    public function rewrite(string $signedUrl): string;
}

