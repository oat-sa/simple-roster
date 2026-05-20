<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Command\Rostering;

use Aws\CloudWatch\CloudWatchClient;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use DateTimeImmutable;
use OAT\SimpleRoster\Command\Rostering\RosteringImportCloudWatchExportCommand;
use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RosteringImportCloudWatchExportCommandTest extends TestCase
{
    public function testDryRunBuildsMetricsAndLogsPayloadWithReferenceId(): void
    {
        $import = (new RosteringImport())
            ->setReferenceId('ref-456')
            ->setStatus(RosteringImport::STATUS_FAILED)
            ->setAttempts(2)
            ->setTotalRows(12)
            ->setProcessedRows(6)
            ->setFailedRows(6)
            ->setErrorMessage('Validation failure')
            ->setStartedAt(new DateTimeImmutable('2026-05-13 11:00:00'))
            ->setFinishedAt(new DateTimeImmutable('2026-05-13 11:00:04'));

        $repository = $this->createMock(RosteringImportRepository::class);
        $repository
            ->expects(self::once())
            ->method('findInWindow')
            ->willReturn([$import]);

        $cloudWatchClient = $this->createMock(CloudWatchClient::class);
        $cloudWatchLogsClient = $this->createMock(CloudWatchLogsClient::class);
        $fileStorage = $this->createMock(FileStorageInterface::class);
        $fileKeyResolver = $this->createMock(RosteringFileKeyResolver::class);

        $fileKeyResolver
            ->expects(self::once())
            ->method('inputFileKey')
            ->with('ref-456')
            ->willReturn('ref-456/input.csv');

        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, "user_username,user_password\njohn,secret\n");
        rewind($stream);

        $fileStorage
            ->expects(self::once())
            ->method('read')
            ->with('ref-456/input.csv')
            ->willReturn($stream);

        $command = new RosteringImportCloudWatchExportCommand(
            'Test/Namespace',
            'sr',
            'test',
            '/test/log-group',
            $repository,
            $cloudWatchClient,
            $cloudWatchLogsClient,
            $fileStorage,
            $fileKeyResolver,
            ',',
            '"',
            '\\'
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['--dry-run' => true]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Prepared metrics payload:', $tester->getDisplay());
        self::assertStringContainsString('Prepared logs payload:', $tester->getDisplay());
        self::assertStringContainsString('ref-456', $tester->getDisplay());
        self::assertStringContainsString('users-classrooms', $tester->getDisplay());
    }
}
