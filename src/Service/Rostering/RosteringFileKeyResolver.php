<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

class RosteringFileKeyResolver
{
    private const INPUT_FILE_NAME = 'input.csv';
    private const SR_OUTPUT_FILE_NAME = 'sr-output.csv';
    private const PP_OUTPUT_FILE_NAME = 'pp-output.csv';
    private const MERGED_OUTPUT_FILE_NAME = 'output.csv';

    public function inputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::INPUT_FILE_NAME);
    }

    public function outputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::SR_OUTPUT_FILE_NAME);
    }

    public function principalPortalOutputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::PP_OUTPUT_FILE_NAME);
    }

    public function mergedOutputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::MERGED_OUTPUT_FILE_NAME);
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
