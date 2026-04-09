<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

interface ResultFileUrlProviderInterface
{
    public function generate(string $fileKey): ?string;
}
