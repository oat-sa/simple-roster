<?php declare(strict_types=1);

namespace App\Generator;

class NonceGenerator
{
    public function generate(): string
    {
        return hash('sha256', uniqid());
    }
}
