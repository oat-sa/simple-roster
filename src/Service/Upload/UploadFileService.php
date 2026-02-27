<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Upload;

use OAT\SimpleRoster\Message\RosteringFileUploadedMessage;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class UploadFileService
{
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
        $extension = strtolower(trim($file->getClientOriginalExtension()));

        $pendingKey = $this->buildPendingKey($referenceId, $extension);

        $message = $this->fileStorage->store(
            $file,
            $pendingKey,
            ['referenceId' => $referenceId]
        );

        $this->messageBus->dispatch(new RosteringFileUploadedMessage($referenceId, $pendingKey));

        return [
            'message' => $message,
            'referenceId' => $referenceId,
        ];
    }

    private function buildPendingKey(string $referenceId, string $extension): string
    {
        $folder = trim($this->s3PendingFolderName, '/');
        $fileName = $referenceId . '.' . ltrim($extension, '.');

        if ($folder === '') {
            return $fileName;
        }

        return $folder . '/' . $fileName;
    }
}
