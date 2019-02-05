<?php declare(strict_types=1);

namespace App\Generator;

class UserCacheIdGenerator
{
    public function generate(string $username): string
    {
        return sprintf('user_%s', hash('sha256', $username));
    }
}
