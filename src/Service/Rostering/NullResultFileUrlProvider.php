<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

class NullResultFileUrlProvider implements ResultFileUrlProviderInterface
{
    public function generate(string $fileKey): ?string
    {
        return null;
    }
}
