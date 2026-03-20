<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;

class RosteringImportStatusService
{
    public function __construct(
        private readonly RosteringImportRepository $rosteringImportRepository,
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly ResultFileUrlProviderInterface $resultFileUrlProvider
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
            $resultFileUrl = $this->resultFileUrlProvider->generate(
                $this->fileKeyResolver->outputFileKey($referenceId)
            );
        }

        return RosteringImportStatus::fromImport($import, $resultFileUrl);
    }

    private function fileExists(string $fileKey): bool
    {
        return $this->fileStorage->exists($fileKey);
    }
}
