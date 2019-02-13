<?php declare(strict_types=1);

namespace App\Generator;

use Carbon\Carbon;

class NonceGenerator
{
    public function generate(): string
    {
        return hash('sha256', (string)Carbon::now()->getTimestamp());
    }
}
