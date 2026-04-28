<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class ZippedCsvExtractor
{
    private const string ZIP_FILE_EXTENSION = 'zip';
    private const int ZIP_READ_CHUNK_SIZE = 8192;

    public function __construct(
        private readonly UploadedFileValidator $validator
    ) {
    }

    public function prepare(UploadedFile $file): PreparedUploadedFile
    {
        $extension = strtolower(trim($file->getClientOriginalExtension()));

        if ($extension !== self::ZIP_FILE_EXTENSION) {
            return new PreparedUploadedFile($file);
        }

        if (!class_exists(ZipArchive::class)) {
            throw new UploadedFileValidationException('ZIP file support is not available.');
        }

        $archive = new ZipArchive();

        if ($archive->open($file->getPathname()) !== true) {
            throw new UploadedFileValidationException('Unable to read uploaded ZIP file.');
        }

        try {
            $fileIndexes = [];

            for ($index = 0; $index < $archive->numFiles; ++$index) {
                $name = $archive->getNameIndex($index);

                if (!is_string($name) || $name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                $fileIndexes[] = $index;
            }

            if (count($fileIndexes) !== 1) {
                throw new UploadedFileValidationException('ZIP archive must contain exactly one file.');
            }

            $fileIndex = $fileIndexes[0];
            $stat = $archive->statIndex($fileIndex);
            $entrySize = $stat['size'] ?? null;

            if (!is_int($entrySize)) {
                throw new UploadedFileValidationException('Unable to determine extracted ZIP file size.');
            }

            $maxSize = $this->validator->getAllowedUploadedFileMaxSize();
            if ($entrySize > $maxSize) {
                throw new UploadedFileValidationException(
                    sprintf(
                        'File size "%d" exceeds maximum allowed size of "%d".',
                        $entrySize,
                        $maxSize
                    )
                );
            }

            $extractedPath = tempnam(sys_get_temp_dir(), 'upload_zip_');
            if (!is_string($extractedPath)) {
                throw new UploadedFileValidationException('Unable to prepare extracted ZIP file for processing.');
            }

            try {
                $this->extractArchiveEntryToPath($archive, $fileIndex, $extractedPath);

                $name = (string) $archive->getNameIndex($fileIndex);
                $originalName = basename($name);

                return new PreparedUploadedFile(
                    new UploadedFile($extractedPath, $originalName, null, null, true),
                    true
                );
            } catch (UploadedFileValidationException $exception) {
                @unlink($extractedPath);

                throw $exception;
            }
        } finally {
            $archive->close();
        }
    }

    private function extractArchiveEntryToPath(ZipArchive $archive, int $fileIndex, string $extractedPath): void
    {
        $name = $archive->getNameIndex($fileIndex);
        $inputStream = is_string($name) ? $archive->getStream($name) : false;
        if (!is_resource($inputStream)) {
            throw new UploadedFileValidationException('Unable to extract file from uploaded ZIP archive.');
        }

        $outputStream = @fopen($extractedPath, 'wb');
        if (!is_resource($outputStream)) {
            fclose($inputStream);

            throw new UploadedFileValidationException('Unable to prepare extracted ZIP file for processing.');
        }

        try {
            while (!feof($inputStream)) {
                $chunk = fread($inputStream, self::ZIP_READ_CHUNK_SIZE);

                if (!is_string($chunk)) {
                    throw new UploadedFileValidationException('Unable to extract file from uploaded ZIP archive.');
                }

                if ($chunk === '') {
                    continue;
                }

                if (fwrite($outputStream, $chunk) === false) {
                    throw new UploadedFileValidationException('Unable to prepare extracted ZIP file for processing.');
                }
            }
        } finally {
            fclose($inputStream);
            fclose($outputStream);
        }
    }
}
