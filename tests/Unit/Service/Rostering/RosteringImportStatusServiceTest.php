<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\PrincipalPortalStatusClientInterface;
use OAT\SimpleRoster\Service\Rostering\ResultFileUrlProviderInterface;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusMerger;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusService;
use OAT\SimpleRoster\Service\Rostering\RosteringResultFileMerger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RosteringImportStatusServiceTest extends TestCase
{
    public function testItReturnsLocalStatusWhenPrincipalPortalIntegrationIsDisabled(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $principalPortalStatusClient = $this->createMock(PrincipalPortalStatusClientInterface::class);
        $statusMerger = $this->createMock(RosteringImportStatusMerger::class);
        $resultFileMerger = $this->createMock(RosteringResultFileMerger::class);
        $resolver = new RosteringFileKeyResolver();

        $import = $this->createProcessedImport('ref-1');
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['referenceId' => 'ref-1'])
            ->willReturn($import);

        $resultFileUrlProvider
            ->expects(self::once())
            ->method('generate')
            ->with($resolver->outputFileKey('ref-1'))
            ->willReturn('http://local-output');

        $principalPortalStatusClient
            ->expects(self::never())
            ->method('fetchStatus');

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $principalPortalStatusClient,
            $statusMerger,
            $resultFileMerger,
            false
        );

        $status = $subject->getStatus('ref-1');

        self::assertInstanceOf(RosteringImportStatus::class, $status);
        self::assertSame('processed', $status->getStatus());
        self::assertSame('http://local-output', $status->getResultFileUrl());
    }

    public function testItReturnsMergedStatusAndMergedOutputFileUrlWhenIntegrationIsEnabled(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $principalPortalStatusClient = $this->createMock(PrincipalPortalStatusClientInterface::class);
        $statusMerger = $this->createMock(RosteringImportStatusMerger::class);
        $resultFileMerger = $this->createMock(RosteringResultFileMerger::class);
        $resolver = new RosteringFileKeyResolver();

        $import = $this->createProcessedImport('ref-2');
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['referenceId' => 'ref-2'])
            ->willReturn($import);

        $resultFileUrlProvider
            ->expects(self::once())
            ->method('generate')
            ->with($resolver->mergedOutputFileKey('ref-2'))
            ->willReturn('http://merged-output');

        $principalPortalStatusClient
            ->expects(self::once())
            ->method('fetchStatus')
            ->with('ref-2')
            ->willReturn(new RosteringImportStatus('ref-2', 'processed', 3, [], null));

        $statusMerger
            ->expects(self::once())
            ->method('merge')
            ->willReturn(new RosteringImportStatus('ref-2', 'processed', 5, [], null));

        $resultFileMerger
            ->expects(self::once())
            ->method('getOrCreateMergedOutputFileKey')
            ->with('ref-2')
            ->willReturn($resolver->mergedOutputFileKey('ref-2'));

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $principalPortalStatusClient,
            $statusMerger,
            $resultFileMerger,
            true
        );

        $status = $subject->getStatus('ref-2');

        self::assertInstanceOf(RosteringImportStatus::class, $status);
        self::assertSame('processed', $status->getStatus());
        self::assertSame('http://merged-output', $status->getResultFileUrl());
    }

    private function createProcessedImport(string $referenceId): RosteringImport
    {
        return (new RosteringImport())
            ->setReferenceId($referenceId)
            ->setStatus(RosteringImport::STATUS_PROCESSED)
            ->setAttempts(1)
            ->setTotalRows(1)
            ->setProcessedRows(1)
            ->setFailedRows(0);
    }

    private function createSubject(
        RosteringImportRepository $repository,
        FileStorageInterface $fileStorage,
        RosteringFileKeyResolver $resolver,
        ResultFileUrlProviderInterface $resultFileUrlProvider,
        PrincipalPortalStatusClientInterface $principalPortalStatusClient,
        RosteringImportStatusMerger $statusMerger,
        RosteringResultFileMerger $resultFileMerger,
        bool $principalPortalEnabled
    ): RosteringImportStatusService {
        return new RosteringImportStatusService(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $principalPortalStatusClient,
            $statusMerger,
            $resultFileMerger,
            $principalPortalEnabled,
            new NullLogger()
        );
    }
}
