<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use IteratorIterator;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;

class RosteringResultFileMerger
{
    private const RESULT_STATUS = 'status';
    private const STATUS_PROCESSED = 'processed';

    public function __construct(
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver
    ) {
    }

    public function getOrCreateMergedOutputFileKey(string $referenceId): string
    {
        $mergedOutputFileKey = $this->fileKeyResolver->mergedOutputFileKey($referenceId);
        if ($this->fileStorage->exists($mergedOutputFileKey)) {
            return $mergedOutputFileKey;
        }

        $srOutputFileKey = $this->fileKeyResolver->outputFileKey($referenceId);
        $ppOutputFileKey = $this->fileKeyResolver->principalPortalOutputFileKey($referenceId);

        if (
            !$this->fileStorage->exists($srOutputFileKey)
            || !$this->fileStorage->exists($ppOutputFileKey)
        ) {
            throw new RosteringStatusException(
                sprintf('Unable to merge output files for "%s": required worker output files are missing.', $referenceId)
            );
        }

        $srStream = null;
        $ppStream = null;
        $mergedStream = null;

        try {
            $srStream = $this->fileStorage->read($srOutputFileKey);
            $ppStream = $this->fileStorage->read($ppOutputFileKey);
            $mergedStream = fopen('php://temp', 'rb+');

            if ($mergedStream === false) {
                throw new RosteringStatusException('Unable to create temporary stream for merged rostering output file.');
            }

            $this->mergeStreams($srStream, $ppStream, $mergedStream);
            rewind($mergedStream);
            $this->fileStorage->store($mergedStream, $mergedOutputFileKey);

            return $mergedOutputFileKey;
        } finally {
            if (is_resource($srStream)) {
                fclose($srStream);
            }

            if (is_resource($ppStream)) {
                fclose($ppStream);
            }

            if (is_resource($mergedStream)) {
                fclose($mergedStream);
            }
        }
    }

    /**
     * @param resource $srStream
     * @param resource $ppStream
     * @param resource $mergedStream
     */
    private function mergeStreams(mixed $srStream, mixed $ppStream, mixed $mergedStream): void
    {
        if (!is_resource($srStream) || !is_resource($ppStream) || !is_resource($mergedStream)) {
            throw new RosteringStatusException('Unable to merge output files: invalid stream resource.');
        }

        $srReader = Reader::from($srStream);
        $srReader->setHeaderOffset(0);
        $ppReader = Reader::from($ppStream);
        $ppReader->setHeaderOffset(0);

        $header = $srReader->getHeader();
        if ($ppReader->getHeader() !== $header) {
            throw new RosteringStatusException('Unable to merge output files: headers mismatch between SR and PP.');
        }

        $writer = Writer::from($mergedStream);
        $writer->insertOne($header);

        $srRows = new IteratorIterator((new Statement())->process($srReader)->getRecords());
        $ppRows = new IteratorIterator((new Statement())->process($ppReader)->getRecords());
        $srRows->rewind();
        $ppRows->rewind();

        while ($srRows->valid() || $ppRows->valid()) {
            $srRow = $srRows->valid() ? $this->normalizeRow($srRows->current(), $header) : null;
            $ppRow = $ppRows->valid() ? $this->normalizeRow($ppRows->current(), $header) : null;

            if ($srRow !== null && $this->isRowEmpty($srRow)) {
                $srRow = null;
            }

            if ($ppRow !== null && $this->isRowEmpty($ppRow)) {
                $ppRow = null;
            }

            foreach ($this->mergeRows($srRow, $ppRow) as $row) {
                $writer->insertOne($this->toCsvLine($header, $row));
            }

            if ($srRows->valid()) {
                $srRows->next();
            }

            if ($ppRows->valid()) {
                $ppRows->next();
            }
        }
    }

    /**
     * @param array<string, string>|null $srRow
     * @param array<string, string>|null $ppRow
     *
     * @return array<int, array<string, string>>
     */
    private function mergeRows(?array $srRow, ?array $ppRow): array
    {
        if ($srRow === null && $ppRow === null) {
            return [];
        }

        if ($srRow === null) {
            if ($ppRow === null) {
                return [];
            }

            return [$ppRow];
        }

        if ($ppRow === null) {
            return [$srRow];
        }

        $srHasError = $this->rowHasError($srRow);
        $ppHasError = $this->rowHasError($ppRow);

        if (!$srHasError && !$ppHasError) {
            return [$srRow];
        }

        if ($srHasError && !$ppHasError) {
            return [$srRow];
        }

        if (!$srHasError && $ppHasError) {
            return [$ppRow];
        }

        return [$srRow, $ppRow];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, string> $header
     *
     * @return array<string, string>
     */
    private function normalizeRow(array $row, array $header): array
    {
        $normalizedRow = [];
        foreach ($header as $column) {
            $value = $row[$column] ?? '';
            $normalizedRow[$column] = is_string($value) ? $value : (string) $value;
        }

        return $normalizedRow;
    }

    /**
     * @param array<string, string> $row
     */
    private function rowHasError(array $row): bool
    {
        return strtolower(trim($row[self::RESULT_STATUS] ?? '')) !== self::STATUS_PROCESSED;
    }

    /**
     * @param array<string, string> $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $header
     * @param array<string, string> $row
     *
     * @return array<int, string>
     */
    private function toCsvLine(array $header, array $row): array
    {
        $line = [];
        foreach ($header as $column) {
            $line[] = $row[$column] ?? '';
        }

        return $line;
    }
}
