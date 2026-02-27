<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadedFileValidator
{
    private const ALLOWED_FILE_EXTENSION = 'csv';

    public function __construct(
        private readonly int $allowedUploadedFileMaxSize
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
        $handle = fopen($file->getPathname(), 'rb');
        if ($handle === false) {
            throw new UploadedFileValidationException('Unable to read uploaded CSV file.');
        }

        $expectedColumns = null;
        $rowNumber = 0;

        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                ++$rowNumber;

                if ($row === [null]) {
                    continue;
                }

                $columnsCount = count($row);

                if ($expectedColumns === null) {
                    $expectedColumns = $columnsCount;
                    continue;
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

            if (!feof($handle)) {
                throw new UploadedFileValidationException('Uploaded file cannot be parsed as valid CSV.');
            }

            if ($expectedColumns === null) {
                throw new UploadedFileValidationException('Uploaded CSV file is empty.');
            }
        } finally {
            fclose($handle);
        }
    }
}
