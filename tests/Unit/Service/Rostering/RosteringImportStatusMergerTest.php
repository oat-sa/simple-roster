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
            new RosteringImportStatus('ref-1', 'processed', 8, [], null),
            new RosteringImportStatus('ref-1', 'processed', 10, [], null)
        );

        self::assertSame('processed', $merged->getStatus());
        self::assertSame(10, $merged->getFileLine());
    }

    public function testItKeepsProcessingWhenBothWorkersAreProcessing(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processing', 4, [], null),
            new RosteringImportStatus('ref-1', 'processing', 9, [], null)
        );

        self::assertSame('processing', $merged->getStatus());
        self::assertSame(9, $merged->getFileLine());
    }

    public function testItMergesStatusesUsingConfiguredPriority(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processing', 7, ['SR processing'], null),
            new RosteringImportStatus('ref-1', 'pending', 10, ['PP pending'], null)
        );

        self::assertSame('pending', $merged->getStatus());
        self::assertSame(10, $merged->getFileLine());
        self::assertSame(['SR processing', 'PP pending'], $merged->getMessages());
    }

    public function testItReturnsFailedWhenAnyWorkerFailed(): void
    {
        $subject = new RosteringImportStatusMerger();

        $merged = $subject->merge(
            new RosteringImportStatus('ref-1', 'processed', 5, [], null),
            new RosteringImportStatus('ref-1', 'failed', 5, ['Import failed in PP'], null)
        );

        self::assertSame('failed', $merged->getStatus());
        self::assertSame(['Import failed in PP'], $merged->getMessages());
    }
}
