<?php declare(strict_types=1);

namespace App\ODM\Id;

class UniqidGenerator implements IdGeneratorInterface
{
    public function generate(?string $prefix = null): string
    {
        if ($prefix) {
            $prefix = hash('crc32b', $prefix, false);
        }

        return str_replace('.', '', uniqid($prefix ?? '', true));
    }
}