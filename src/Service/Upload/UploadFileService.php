<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class UploadFileService
{
    private const UPLOAD_SUCCESS_MESSAGE = 'File uploaded';

    public function __construct(
        private readonly UploadedFileValidator $validator,
        private readonly FileStorageInterface $fileStorage,
        private readonly MessageBusInterface $messageBus,
        private readonly string $s3PendingFolderName
    ) {
    }

    /**
     * @return array{message: string, referenceId: string}
     */
    public function upload(UploadedFile $file): array
    {
        $this->validator->validate($file);

        $referenceId = Uuid::uuid4()->toString();
        $storageKey = $this->buildStorageKey($referenceId);

        $this->fileStorage->store(
            $file,
            $storageKey,
            ['referenceId' => $referenceId]
        );

        $this->messageBus->dispatch(new RosteringFileUploadedMessage($referenceId));

        return [
            'message' => self::UPLOAD_SUCCESS_MESSAGE,
            'referenceId' => $referenceId,
        ];
    }

    private function buildStorageKey(string $referenceId): string
    {
        $folder = trim($this->s3PendingFolderName, '/');
        $fileName = $referenceId . '.csv';

        return $folder ? $folder . '/' . $fileName : $fileName;
    }
}
