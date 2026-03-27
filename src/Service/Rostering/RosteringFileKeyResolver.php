<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

class RosteringFileKeyResolver
{
    private const INPUT_FILE_NAME = 'input.csv';
    private const OUTPUT_FILE_NAME = 'sr-output.csv';
    private const EXTERNAL_REPORTING_SYSTEM_OUTPUT_FILE_NAME = 'external-reporting-system-output.csv';
    private const MERGED_OUTPUT_FILE_NAME = 'output.csv';

    public function inputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::INPUT_FILE_NAME);
    }

    public function outputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::OUTPUT_FILE_NAME);
    }

    public function externalReportingSystemOutputFileKey(string $referenceId): string
    {
        return sprintf('%s/%s', $referenceId, self::EXTERNAL_REPORTING_SYSTEM_OUTPUT_FILE_NAME);
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
