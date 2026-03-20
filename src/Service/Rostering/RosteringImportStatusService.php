<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use Aws\S3\S3Client;
use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;
use Throwable;

class RosteringImportStatusService
{
    public function __construct(
        private readonly RosteringImportRepository $rosteringImportRepository,
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly S3Client $s3Client,
        private readonly string $bucket,
        private readonly string $prefix,
        private readonly string $kernelEnvironment,
        private readonly string $signedUrlTtl
    ) {
    }

    public function getStatus(string $referenceId): ?RosteringImportStatus
    {
        $import = $this->rosteringImportRepository->findOneBy(['referenceId' => $referenceId]);
        if (!$import instanceof RosteringImport) {
            if ($this->fileExists($this->fileKeyResolver->inputFileKey($referenceId))) {
                return RosteringImportStatus::pending($referenceId);
            }

            return null;
        }

        $resultFileUrl = null;
        if ($import->getStatus() === RosteringImport::STATUS_PROCESSED) {
            $resultFileUrl = $this->buildResultFileUrl($referenceId);
        }

        return RosteringImportStatus::fromImport($import, $resultFileUrl);
    }

    private function buildResultFileUrl(string $referenceId): ?string
    {
        if ($this->kernelEnvironment !== 'prod') {
            return null;
        }

        if (trim($this->bucket) === '') {
            return null;
        }

        $resultFileKey = $this->fileKeyResolver->outputFileKey($referenceId);
        if (!$this->fileExists($resultFileKey)) {
            return null;
        }

        $objectKey = $this->fileKeyResolver->objectKey($resultFileKey, $this->prefix);

        try {
            $command = $this->s3Client->getCommand(
                'GetObject',
                [
                    'Bucket' => $this->bucket,
                    'Key' => $objectKey,
                ]
            );
            $request = $this->s3Client->createPresignedRequest($command, $this->signedUrlTtl);

            return (string) $request->getUri();
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function fileExists(string $fileKey): bool
    {
        return $this->fileStorage->exists($fileKey);
    }
}
