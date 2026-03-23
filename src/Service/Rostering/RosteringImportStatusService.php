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
            $localStatus = $this->resolveLocalStatus($referenceId, true);
            if ($localStatus === null) {
                return null;
            }

            return $localStatus;
        }

        $localStatus = $this->resolveLocalStatus($referenceId, false);
        if ($localStatus === null) {
            return null;
        }

        try {
            $principalPortalStatus = $this->principalPortalStatusClient->fetchStatus($referenceId);
            $mergedStatus = $this->statusMerger->merge($localStatus, $principalPortalStatus);
            if (!$mergedStatus->isProcessed()) {
                return $mergedStatus;
            }

            $mergedOutputFileKey = $this->resultFileMerger->getOrCreateMergedOutputFileKey($referenceId);

            return $mergedStatus->withResultFileUrl($this->resultFileUrlProvider->generate($mergedOutputFileKey));
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

    private function resolveLocalStatus(string $referenceId, bool $withResultFileUrl): ?RosteringImportStatus
    {
        $import = $this->rosteringImportRepository->findOneBy(['referenceId' => $referenceId]);
        if (!$import instanceof RosteringImport) {
            if ($this->fileStorage->exists($this->fileKeyResolver->inputFileKey($referenceId))) {
                return RosteringImportStatus::pending($referenceId);
            }

            return null;
        }

        $resultFileUrl = $withResultFileUrl && $import->isProcessed()
            ? $this->resultFileUrlProvider->generate($this->fileKeyResolver->outputFileKey($referenceId))
            : null;

        return RosteringImportStatus::fromImport($import, $resultFileUrl);
    }
}
