<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

class RosteringFileKeyResolver
{
    private const INPUT_FILE_NAME = 'input.csv';
    private const OUTPUT_FILE_NAME = 'sr-output.csv';

    public function inputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::INPUT_FILE_NAME);
    }

    public function outputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::OUTPUT_FILE_NAME);
    }

    public function objectKey(string $fileKey, string $prefix): string
    {
        $trimmedPrefix = trim($prefix, '/');
        if ($trimmedPrefix === '') {
            return $fileKey;
        }

        return sprintf('%s/%s', $trimmedPrefix, $fileKey);
    }
}
