<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use League\Csv\Reader;
use League\Csv\SyntaxError;
use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class UploadedFileValidator
{
    private const ALLOWED_FILE_EXTENSION = 'csv';

    public function __construct(
        private readonly int $allowedUploadedFileMaxSize,
        private readonly int $allowedUploadedFileMaxRecords,
        private readonly string $uploadedFileCsvDelimiter,
        private readonly string $uploadedFileCsvEnclosure,
        private readonly string $uploadedFileCsvEscape
    ) {
    }

    public function validate(UploadedFile $file): void
    {
        $extension = strtolower(trim((string) $file->getClientOriginalExtension()));

        if ($extension === '') {
            throw new UploadedFileValidationException('File extension is missing.');
        }

        if ($extension !== self::ALLOWED_FILE_EXTENSION) {
            throw new UploadedFileValidationException(
                sprintf(
                    'File extension "%s" is not allowed. Allowed extensions are: %s',
                    $extension,
                    self::ALLOWED_FILE_EXTENSION
                )
            );
        }

        $size = $file->getSize();

        if (!is_int($size)) {
            throw new UploadedFileValidationException('Unable to determine file size.');
        }

        if ($size > $this->allowedUploadedFileMaxSize) {
            throw new UploadedFileValidationException(
                sprintf(
                    'File size "%d" exceeds maximum allowed size of "%d".',
                    $size,
                    $this->allowedUploadedFileMaxSize
                )
            );
        }

        $this->validateCsvStructure($file);
    }

    private function validateCsvStructure(UploadedFile $file): void
    {
        try {
            $csv = Reader::from($file->getPathname(), 'r')
                ->setDelimiter($this->uploadedFileCsvDelimiter)
                ->setEnclosure($this->uploadedFileCsvEnclosure)
                ->setEscape($this->uploadedFileCsvEscape);
        } catch (Throwable $exception) {
            throw new UploadedFileValidationException('Unable to read uploaded CSV file.', 0, $exception);
        }

        $expectedColumns = null;
        $rowNumber = 0;
        $recordCount = 0;

        try {
            foreach ($csv->getRecords() as $row) {
                ++$rowNumber;

                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                $columnsCount = count($row);

                if ($expectedColumns === null) {
                    $expectedColumns = $columnsCount;
                    continue;
                }

                ++$recordCount;
                if ($recordCount > $this->allowedUploadedFileMaxRecords) {
                    throw new UploadedFileValidationException(
                        sprintf(
                            'File records count "%d" exceeds maximum allowed records of "%d".',
                            $recordCount,
                            $this->allowedUploadedFileMaxRecords
                        )
                    );
                }

                if ($columnsCount !== $expectedColumns) {
                    throw new UploadedFileValidationException(
                        sprintf(
                            'Invalid CSV structure detected at row "%d". Expected "%d" columns, got "%d".',
                            $rowNumber,
                            $expectedColumns,
                            $columnsCount
                        )
                    );
                }
            }
        } catch (SyntaxError $exception) {
            throw new UploadedFileValidationException('Uploaded file cannot be parsed as valid CSV.', 0, $exception);
        }

        if ($expectedColumns === null) {
            throw new UploadedFileValidationException('Uploaded CSV file is empty.');
        }
    }

    /**
     * @param array<int, mixed> $row
     */
    private function isEmptyCsvRow(array $row): bool
    {
        if ($row === [] || $row === [null]) {
            return true;
        }

        return array_filter(
            $row,
            static fn ($value): bool => is_string($value) ? trim($value) !== '' : $value !== null
        ) === [];
    }
}
