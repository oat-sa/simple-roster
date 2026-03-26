<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\Rostering;

use OAT\SimpleRoster\Service\Rostering\Dto\RosteringImportStatus;
use OAT\SimpleRoster\Service\Rostering\RosteringImportStatusMerger;
use PHPUnit\Framework\TestCase;

class RosteringImportStatusMergerTest extends TestCase
{
    public function testItKeepsProcessedWhenBothWorkersAreProcessed(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processed', 8, []),
            new RosteringImportStatus('ref-1', 'processed', 10, [])
        );

        self::assertSame('processed', $merged->getStatus());
        self::assertSame(10, $merged->getFileLine());
    }

    public function testItKeepsProcessingWhenBothWorkersAreProcessing(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processing', 4, []),
            new RosteringImportStatus('ref-1', 'processing', 9, [])
        );

        self::assertSame('processing', $merged->getStatus());
        self::assertSame(9, $merged->getFileLine());
    }

    public function testItMergesStatusesUsingConfiguredPriority(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processing', 7, ['SR processing']),
            new RosteringImportStatus('ref-1', 'pending', 10, ['PP pending'])
        );

        self::assertSame('pending', $merged->getStatus());
        self::assertSame(10, $merged->getFileLine());
        self::assertSame(['SR processing', 'PP pending'], $merged->getMessages());
    }

    public function testItReturnsFailedWhenAnyWorkerFailed(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processed', 5, []),
            new RosteringImportStatus('ref-1', 'failed', 5, ['Import failed in PP'])
        );

        self::assertSame('failed', $merged->getStatus());
        self::assertSame(['Import failed in PP'], $merged->getMessages());
    }
}
