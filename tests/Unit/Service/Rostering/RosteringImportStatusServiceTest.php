<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\ExternalReportingStatusClientInterface;
use OAT\SimpleRoster\Service\Rostering\ResultFileUrlProviderInterface;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusMerger;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusService;
use OAT\SimpleRoster\Service\Rostering\RosteringResultFileMerger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class RosteringImportStatusServiceTest extends TestCase
{
    public function testItReturnsLocalStatusWhenExternalReportingSystemIntegrationIsDisabled(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $externalReportingStatusClient = $this->createMock(ExternalReportingStatusClientInterface::class);
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
            ->expects(self::never())
            ->method('generate');

        $externalReportingStatusClient
            ->expects(self::never())
            ->method('fetchStatus');

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $externalReportingStatusClient,
            $statusMerger,
            $resultFileMerger,
            false
        );

        $status = $subject->getStatus('ref-1');

        self::assertInstanceOf(RosteringImportStatus::class, $status);
        self::assertSame('processed', $status->getStatus());
    }

    public function testItReturnsMergedStatusWhenIntegrationIsEnabled(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $externalReportingStatusClient = $this->createMock(ExternalReportingStatusClientInterface::class);
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
            ->expects(self::never())
            ->method('generate');

        $externalReportingStatusClient
            ->expects(self::once())
            ->method('fetchStatus')
            ->with('ref-2')
            ->willReturn(new RosteringImportStatus('ref-2', 'processed', 3, []));

        $statusMerger
            ->expects(self::once())
            ->method('merge')
            ->willReturn(new RosteringImportStatus('ref-2', 'processed', 5, []));

        $resultFileMerger
            ->expects(self::never())
            ->method('getOrCreateMergedOutputFileKey');

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $externalReportingStatusClient,
            $statusMerger,
            $resultFileMerger,
            true
        );

        $status = $subject->getStatus('ref-2');

        self::assertInstanceOf(RosteringImportStatus::class, $status);
        self::assertSame('processed', $status->getStatus());
    }

    public function testItReturnsLocalDownloadUrlWhenProcessedAndIntegrationIsDisabled(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $externalReportingStatusClient = $this->createMock(ExternalReportingStatusClientInterface::class);
        $statusMerger = $this->createMock(RosteringImportStatusMerger::class);
        $resultFileMerger = $this->createMock(RosteringResultFileMerger::class);
        $resolver = new RosteringFileKeyResolver();

        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['referenceId' => 'ref-3'])
            ->willReturn($this->createProcessedImport('ref-3'));

        $resultFileUrlProvider
            ->expects(self::once())
            ->method('generate')
            ->with($resolver->outputFileKey('ref-3'))
            ->willReturn('http://local-output');

        $externalReportingStatusClient
            ->expects(self::never())
            ->method('fetchStatus');

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $externalReportingStatusClient,
            $statusMerger,
            $resultFileMerger,
            false
        );

        self::assertSame('http://local-output', $subject->getDownloadUrl('ref-3'));
    }

    public function testItReturnsMergedDownloadUrlWhenProcessedAndIntegrationIsEnabled(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $externalReportingStatusClient = $this->createMock(ExternalReportingStatusClientInterface::class);
        $statusMerger = $this->createMock(RosteringImportStatusMerger::class);
        $resultFileMerger = $this->createMock(RosteringResultFileMerger::class);
        $resolver = new RosteringFileKeyResolver();

        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['referenceId' => 'ref-4'])
            ->willReturn($this->createProcessedImport('ref-4'));

        $externalReportingStatusClient
            ->expects(self::once())
            ->method('fetchStatus')
            ->with('ref-4')
            ->willReturn(new RosteringImportStatus('ref-4', 'processed', 3, []));

        $statusMerger
            ->expects(self::once())
            ->method('merge')
            ->willReturn(new RosteringImportStatus('ref-4', 'processed', 5, []));

        $fileStorage
            ->expects(self::once())
            ->method('exists')
            ->with($resolver->externalReportingSystemOutputFileKey('ref-4'))
            ->willReturn(true);

        $resultFileMerger
            ->expects(self::once())
            ->method('getOrCreateMergedOutputFileKey')
            ->with('ref-4')
            ->willReturn($resolver->mergedOutputFileKey('ref-4'));

        $resultFileUrlProvider
            ->expects(self::once())
            ->method('generate')
            ->with($resolver->mergedOutputFileKey('ref-4'))
            ->willReturn('http://merged-output');

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $externalReportingStatusClient,
            $statusMerger,
            $resultFileMerger,
            true
        );

        self::assertSame('http://merged-output', $subject->getDownloadUrl('ref-4'));
    }

    public function testItReturnsNullWhenExternalReportingSystemOutputIsNotReady(): void
    {
        $repository = $this->createMock(RosteringImportRepository::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $resultFileUrlProvider = $this->createMock(ResultFileUrlProviderInterface::class);
        $externalReportingStatusClient = $this->createMock(ExternalReportingStatusClientInterface::class);
        $statusMerger = $this->createMock(RosteringImportStatusMerger::class);
        $resultFileMerger = $this->createMock(RosteringResultFileMerger::class);
        $resolver = new RosteringFileKeyResolver();

        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['referenceId' => 'ref-5'])
            ->willReturn($this->createProcessedImport('ref-5'));

        $externalReportingStatusClient
            ->expects(self::once())
            ->method('fetchStatus')
            ->with('ref-5')
            ->willReturn(new RosteringImportStatus('ref-5', 'processed', 3, []));

        $statusMerger
            ->expects(self::once())
            ->method('merge')
            ->willReturn(new RosteringImportStatus('ref-5', 'processed', 5, []));

        $fileStorage
            ->expects(self::once())
            ->method('exists')
            ->with($resolver->externalReportingSystemOutputFileKey('ref-5'))
            ->willReturn(false);

        $resultFileMerger
            ->expects(self::never())
            ->method('getOrCreateMergedOutputFileKey');

        $resultFileUrlProvider
            ->expects(self::never())
            ->method('generate');

        $subject = $this->createSubject(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $externalReportingStatusClient,
            $statusMerger,
            $resultFileMerger,
            true
        );

        self::assertNull($subject->getDownloadUrl('ref-5'));
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
        ExternalReportingStatusClientInterface $externalReportingStatusClient,
        RosteringImportStatusMerger $statusMerger,
        RosteringResultFileMerger $resultFileMerger,
        bool $externalReportingSystemEnabled
    ): RosteringImportStatusService {
        return new RosteringImportStatusService(
            $repository,
            $fileStorage,
            $resolver,
            $resultFileUrlProvider,
            $externalReportingStatusClient,
            $statusMerger,
            $resultFileMerger,
            $externalReportingSystemEnabled,
            new NullLogger()
        );
    }
}
