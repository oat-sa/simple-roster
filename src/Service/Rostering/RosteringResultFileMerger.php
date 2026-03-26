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
        $externalReportingSystemOutputFileKey = $this->fileKeyResolver->externalReportingSystemOutputFileKey($referenceId);

        if (
            !$this->fileStorage->exists($srOutputFileKey)
            || !$this->fileStorage->exists($externalReportingSystemOutputFileKey)
        ) {
            throw new RosteringStatusException(
                sprintf('Unable to merge output files for "%s": required worker output files are missing.', $referenceId)
            );
        }

        $srStream = null;
        $externalReportingSystemStream = null;
        $mergedStream = null;

        try {
            $srStream = $this->fileStorage->read($srOutputFileKey);
            $externalReportingSystemStream = $this->fileStorage->read($externalReportingSystemOutputFileKey);
            $mergedStream = fopen('php://temp', 'rb+');

            if ($mergedStream === false) {
                throw new RosteringStatusException('Unable to create temporary stream for merged rostering output file.');
            }

            $this->mergeStreams($srStream, $externalReportingSystemStream, $mergedStream);
            rewind($mergedStream);
            $this->fileStorage->store($mergedStream, $mergedOutputFileKey);

            return $mergedOutputFileKey;
        } finally {
            if (is_resource($srStream)) {
                fclose($srStream);
            }

            if (is_resource($externalReportingSystemStream)) {
                fclose($externalReportingSystemStream);
            }

            if (is_resource($mergedStream)) {
                fclose($mergedStream);
            }
        }
    }

    /**
     * @param resource $srStream
     * @param resource $externalReportingSystemStream
     * @param resource $mergedStream
     */
    private function mergeStreams(mixed $srStream, mixed $externalReportingSystemStream, mixed $mergedStream): void
    {
        if (!is_resource($srStream) || !is_resource($externalReportingSystemStream) || !is_resource($mergedStream)) {
            throw new RosteringStatusException('Unable to merge output files: invalid stream resource.');
        }

        $srReader = Reader::from($srStream);
        $srReader->setHeaderOffset(0);
        $externalReportingSystemReader = Reader::from($externalReportingSystemStream);
        $externalReportingSystemReader->setHeaderOffset(0);

        $header = $srReader->getHeader();
        if ($externalReportingSystemReader->getHeader() !== $header) {
            throw new RosteringStatusException(
                'Unable to merge output files: headers mismatch between SR and external reporting system.'
            );
        }

        $writer = Writer::from($mergedStream);
        $writer->insertOne($header);

        $srRows = new IteratorIterator((new Statement())->process($srReader)->getRecords());
        $externalReportingSystemRows = new IteratorIterator((new Statement())->process($externalReportingSystemReader)->getRecords());
        $srRows->rewind();
        $externalReportingSystemRows->rewind();

        while ($srRows->valid() || $externalReportingSystemRows->valid()) {
            $srRow = $srRows->valid() ? $this->normalizeRow($srRows->current(), $header) : null;
            $externalReportingSystemRow = $externalReportingSystemRows->valid()
                ? $this->normalizeRow($externalReportingSystemRows->current(), $header)
                : null;

            if ($srRow !== null && $this->isRowEmpty($srRow)) {
                $srRow = null;
            }

            if ($externalReportingSystemRow !== null && $this->isRowEmpty($externalReportingSystemRow)) {
                $externalReportingSystemRow = null;
            }

            foreach ($this->mergeRows($srRow, $externalReportingSystemRow) as $row) {
                $writer->insertOne($this->toCsvLine($header, $row));
            }

            if ($srRows->valid()) {
                $srRows->next();
            }

            if ($externalReportingSystemRows->valid()) {
                $externalReportingSystemRows->next();
            }
        }
    }

    /**
     * @param array<string, string>|null $srRow
     * @param array<string, string>|null $externalReportingSystemRow
     *
     * @return array<int, array<string, string>>
     */
    private function mergeRows(?array $srRow, ?array $externalReportingSystemRow): array
    {
        if ($srRow === null && $externalReportingSystemRow === null) {
            return [];
        }

        if ($srRow === null) {
            if ($externalReportingSystemRow === null) {
                return [];
            }

            return [$externalReportingSystemRow];
        }

        if ($externalReportingSystemRow === null) {
            return [$srRow];
        }

        $srHasError = $this->rowHasError($srRow);
        $externalReportingSystemHasError = $this->rowHasError($externalReportingSystemRow);

        if (!$srHasError && !$externalReportingSystemHasError) {
            return [$srRow];
        }

        if ($srHasError && !$externalReportingSystemHasError) {
            return [$srRow];
        }

        if (!$srHasError && $externalReportingSystemHasError) {
            return [$externalReportingSystemRow];
        }

        return [$srRow, $externalReportingSystemRow];
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
