<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringStatusException;
use Psr\Log\LoggerInterface;

class RosteringImportStatusService
{
    public function __construct(
        private readonly RosteringImportRepository $rosteringImportRepository,
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly ResultFileUrlProviderInterface $resultFileUrlProvider,
        private readonly PrincipalPortalStatusClientInterface $principalPortalStatusClient,
        private readonly RosteringImportStatusMerger $statusMerger,
        private readonly RosteringResultFileMerger $resultFileMerger,
        private readonly bool $principalPortalEnabled,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getStatus(string $referenceId): ?RosteringImportStatus
    {
        if (!$this->principalPortalEnabled) {
            return $this->resolveLocalStatus($referenceId);
        }

        $localStatus = $this->resolveLocalStatus($referenceId);
        if ($localStatus === null) {
            return null;
        }

        try {
            $principalPortalStatus = $this->principalPortalStatusClient->fetchStatus($referenceId);
            return $this->statusMerger->merge($localStatus, $principalPortalStatus);
        } catch (RosteringStatusException $exception) {
            $this->logger->error(
                sprintf('Unable to resolve merged rostering status for referenceId "%s".', $referenceId),
                [
                    'referenceId' => $referenceId,
                    'exception' => $exception,
                ]
            );

            throw $exception;
        }
    }

    public function getDownloadUrl(string $referenceId): ?string
    {
        $status = $this->getStatus($referenceId);
        if ($status === null || !$status->isProcessed()) {
            return null;
        }

        if (!$this->principalPortalEnabled) {
            return $this->resultFileUrlProvider->generate($this->fileKeyResolver->outputFileKey($referenceId));
        }

        $mergedOutputFileKey = $this->resultFileMerger->getOrCreateMergedOutputFileKey($referenceId);

        return $this->resultFileUrlProvider->generate($mergedOutputFileKey);
    }

    private function resolveLocalStatus(string $referenceId): ?RosteringImportStatus
    {
        $import = $this->rosteringImportRepository->findOneBy(['referenceId' => $referenceId]);
        if (!$import instanceof RosteringImport) {
            if ($this->fileStorage->exists($this->fileKeyResolver->inputFileKey($referenceId))) {
                return RosteringImportStatus::pending($referenceId);
            }

            return null;
        }

        return RosteringImportStatus::fromImport($import);
    }
}
