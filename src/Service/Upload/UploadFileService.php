<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use OAT\SimpleRoster\Service\Upload\Exception\UploadedFileValidationException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use ZipArchive;

class UploadFileService
{
    private const UPLOAD_SUCCESS_MESSAGE = 'File uploaded';
    private const ZIP_FILE_EXTENSION = 'zip';

    public function __construct(
        private readonly UploadedFileValidator $validator,
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * @return array{message: string, referenceId: string}
     */
    public function upload(UploadedFile $file): array
    {
        $fileToProcess = $this->extractFileFromZipIfNeeded($file);

        try {
            $this->validator->validate($fileToProcess);

            $referenceId = Uuid::uuid7()->toString();
            $storageKey = $this->fileKeyResolver->inputFileKey($referenceId);

            $this->fileStorage->store(
                $fileToProcess,
                $storageKey,
                ['referenceId' => $referenceId]
            );

            $this->messageBus->dispatch(new RosteringFileUploadedMessage($referenceId));

            return [
                'message' => self::UPLOAD_SUCCESS_MESSAGE,
                'referenceId' => $referenceId,
            ];
        } finally {
            if ($fileToProcess !== $file) {
                @unlink($fileToProcess->getPathname());
            }
        }
    }

    private function extractFileFromZipIfNeeded(UploadedFile $file): UploadedFile
    {
        $extension = strtolower(trim($file->getClientOriginalExtension()));

        if ($extension !== self::ZIP_FILE_EXTENSION) {
            return $file;
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
            $content = $archive->getFromIndex($fileIndex);

            if (!is_string($content)) {
                throw new UploadedFileValidationException('Unable to extract file from uploaded ZIP archive.');
            }

            $extractedPath = tempnam(sys_get_temp_dir(), 'upload_zip_');
            if (!is_string($extractedPath) || file_put_contents($extractedPath, $content) === false) {
                throw new UploadedFileValidationException('Unable to prepare extracted ZIP file for processing.');
            }

            $name = (string) $archive->getNameIndex($fileIndex);
            $originalName = basename($name);

            return new UploadedFile($extractedPath, $originalName, null, null, true);
        } finally {
            $archive->close();
        }
    }
}
