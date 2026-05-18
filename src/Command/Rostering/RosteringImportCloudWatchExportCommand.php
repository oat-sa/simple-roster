<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Command\Rostering;

use Aws\CloudWatch\CloudWatchClient;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Exception\AwsException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use OAT\SimpleRoster\Entity\RosteringImport;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Service\Rostering\FileStorageInterface;
use OAT\SimpleRoster\Service\Rostering\RosteringFileKeyResolver;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringUserRowValidator;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class RosteringImportCloudWatchExportCommand extends Command
{
    public const NAME = 'roster:rostering:metrics:export';
    private const DEFAULT_LOG_STREAM_PREFIX = 'rostering-import';

    private const OPTION_WINDOW_MINUTES = 'window-minutes';
    private const OPTION_NAMESPACE = 'namespace';
    private const OPTION_DRY_RUN = 'dry-run';
    private const IMPORT_TYPE_PRINCIPALS_COLLEGE = 'principals-college';
    private const IMPORT_TYPE_USERS_CLASSROOMS = 'users-classrooms';
    private const IMPORT_TYPE_UNKNOWN = 'unknown';
    private const FIELD_PRINCIPAL_USERNAME = 'principal_username';

    public function __construct(
        private readonly string $defaultNamespace,
        private readonly string $projectName,
        private readonly string $environmentName,
        private readonly string $logGroupName,
        private readonly RosteringImportRepository $rosteringImportRepository,
        private readonly CloudWatchClient $cloudWatchClient,
        private readonly CloudWatchLogsClient $cloudWatchLogsClient,
        private readonly FileStorageInterface $fileStorage,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly string $uploadedFileCsvDelimiter,
        private readonly string $uploadedFileCsvEnclosure,
        private readonly string $uploadedFileCsvEscape
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Export rostering import metrics/events to AWS CloudWatch.')
            ->addOption(
                self::OPTION_WINDOW_MINUTES,
                null,
                InputOption::VALUE_OPTIONAL,
                'Sliding window in minutes used to aggregate rostering import metrics.',
                '60'
            )
            ->addOption(
                self::OPTION_NAMESPACE,
                null,
                InputOption::VALUE_OPTIONAL,
                'CloudWatch namespace.',
                ''
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Build and print payloads without sending anything to CloudWatch.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);

        $windowMinutes = (int) $input->getOption(self::OPTION_WINDOW_MINUTES);
        if ($windowMinutes <= 0) {
            $io->error('Option --window-minutes must be greater than 0.');

            return self::INVALID;
        }

        $namespace = $this->resolveOptionValue((string) $input->getOption(self::OPTION_NAMESPACE), $this->defaultNamespace);
        if ($namespace === '') {
            $io->error('CloudWatch namespace is required.');

            return self::INVALID;
        }

        $executedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $from = $executedAt->modify(sprintf('-%d minutes', $windowMinutes));
        $imports = $this->rosteringImportRepository->findInWindow($from, $executedAt);
        $importTypesByReferenceId = $this->resolveImportTypesByReferenceId($imports);
        $metricData = $this->buildMetricData($imports, $importTypesByReferenceId, $executedAt);
        $logEvents = $this->buildLogEvents($imports, $importTypesByReferenceId, $executedAt);

        if ($isDryRun) {
            $io->writeln('Prepared metrics payload:');
            $io->writeln(json_encode(['Namespace' => $namespace, 'MetricData' => $metricData], JSON_PRETTY_PRINT));
            $io->writeln('');
            $io->writeln('Prepared logs payload:');
            $io->writeln(json_encode(['LogGroupName' => $this->logGroupName, 'LogEvents' => $logEvents], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        try {
            if ($metricData !== []) {
                $this->cloudWatchClient->putMetricData(
                    [
                        'Namespace' => $namespace,
                        'MetricData' => $metricData,
                    ]
                );
            }
            $publishedLogEventsCount = $this->publishLogEvents($logEvents, $executedAt);
        } catch (Throwable $exception) {
            $io->error(sprintf('Unable to publish CloudWatch data: %s', $exception->getMessage()));

            return self::FAILURE;
        }

        $io->success(
            sprintf(
                'Published %d metrics and %d log events for %d imports (%s -> %s).',
                count($metricData),
                $publishedLogEventsCount,
                count($imports),
                $from->format(DATE_ATOM),
                $executedAt->format(DATE_ATOM)
            )
        );

        return self::SUCCESS;
    }

    /**
     * @param RosteringImport[] $imports
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildMetricData(
        array $imports,
        array $importTypesByReferenceId,
        DateTimeImmutable $timestamp
    ): array
    {
        $metricBuckets = [];

        foreach ($imports as $import) {
            $importType = $importTypesByReferenceId[$import->getReferenceId()] ?? self::IMPORT_TYPE_UNKNOWN;
            if (!isset($metricBuckets[$importType])) {
                $metricBuckets[$importType] = $this->createEmptyMetricBucket();
            }

            $bucket = &$metricBuckets[$importType];
            $status = $import->getStatus();
            if (isset($bucket['statusCounts'][$status])) {
                ++$bucket['statusCounts'][$status];
            }

            ++$bucket['runsTotal'];
            $bucket['totalRows'] += max(0, (int) ($import->getTotalRows() ?? 0));
            $bucket['processedRows'] += max(0, (int) ($import->getProcessedRows() ?? 0));
            $bucket['failedRows'] += max(0, (int) ($import->getFailedRows() ?? 0));
            $bucket['attemptsTotal'] += max(0, $import->getAttempts());

            $duration = $this->resolveDurationSeconds($import);
            if ($duration === null) {
                unset($bucket);
                continue;
            }

            $bucket['durationSum'] += $duration;
            ++$bucket['durationCount'];
            if ($duration > $bucket['maxDuration']) {
                $bucket['maxDuration'] = $duration;
            }

            unset($bucket);
        }

        if ($metricBuckets === []) {
            $metricBuckets[self::IMPORT_TYPE_UNKNOWN] = $this->createEmptyMetricBucket();
        }

        $metricData = [];
        foreach ($metricBuckets as $importType => $bucket) {
            $durationAvg = $bucket['durationCount'] > 0 ? $bucket['durationSum'] / $bucket['durationCount'] : 0.0;

            $baseDimensions = [
                ['Name' => 'Project', 'Value' => $this->projectName],
                ['Name' => 'Environment', 'Value' => $this->environmentName],
                ['Name' => 'ImportType', 'Value' => $importType],
            ];

            $this->addMetric($metricData, 'RosteringImportRunsTotal', $bucket['runsTotal'], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportRunsProcessing', $bucket['statusCounts'][RosteringImport::STATUS_PROCESSING], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportRunsProcessed', $bucket['statusCounts'][RosteringImport::STATUS_PROCESSED], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportRunsFailed', $bucket['statusCounts'][RosteringImport::STATUS_FAILED], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportRowsTotal', $bucket['totalRows'], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportRowsProcessed', $bucket['processedRows'], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportRowsFailed', $bucket['failedRows'], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportAttemptsTotal', $bucket['attemptsTotal'], 'Count', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportDurationSecondsAvg', $durationAvg, 'Seconds', $baseDimensions, $timestamp);
            $this->addMetric($metricData, 'RosteringImportDurationSecondsMax', $bucket['maxDuration'], 'Seconds', $baseDimensions, $timestamp);
        }

        return $metricData;
    }

    /**
     * @param RosteringImport[] $imports
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildLogEvents(array $imports, array $importTypesByReferenceId, DateTimeImmutable $executedAt): array
    {
        $events = [];

        foreach ($imports as $import) {
            $eventTimestampMs = $this->resolveEventTimestampMs($import, $executedAt);
            $importType = $importTypesByReferenceId[$import->getReferenceId()] ?? self::IMPORT_TYPE_UNKNOWN;
            $eventPayload = [
                'referenceId' => $import->getReferenceId(),
                'status' => $import->getStatus(),
                'importType' => $importType,
                'project' => $this->projectName,
                'environment' => $this->environmentName,
                'attempts' => $import->getAttempts(),
                'totalRows' => $import->getTotalRows(),
                'processedRows' => $import->getProcessedRows(),
                'failedRows' => $import->getFailedRows(),
                'startedAt' => $this->formatDateTime($import->getStartedAt()),
                'finishedAt' => $this->formatDateTime($import->getFinishedAt()),
                'errorMessage' => $import->getErrorMessage(),
            ];

            $events[] = [
                'timestamp' => $eventTimestampMs,
                'message' => (string) json_encode($eventPayload, JSON_UNESCAPED_SLASHES),
            ];
        }

        usort(
            $events,
            static fn(array $left, array $right): int => $left['timestamp'] <=> $right['timestamp']
        );

        return $events;
    }

    /**
     * @param array<int, array<string, mixed>> $metricData
     * @param array<int, array<string, string>> $dimensions
     */
    private function addMetric(
        array &$metricData,
        string $metricName,
        float $value,
        string $unit,
        array $dimensions,
        DateTimeImmutable $timestamp
    ): void {
        $metricData[] = [
            'MetricName' => $metricName,
            'Timestamp' => $timestamp,
            'Value' => $value,
            'Unit' => $unit,
            'Dimensions' => $dimensions,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $logEvents
     */
    private function publishLogEvents(array $logEvents, DateTimeImmutable $executedAt): int
    {
        if ($logEvents === []) {
            return 0;
        }

        if (trim($this->logGroupName) === '') {
            throw new RuntimeException('CloudWatch log group name is required.');
        }

        $logStreamName = $this->createLogStreamName($executedAt);

        try {
            $this->cloudWatchLogsClient->createLogStream(
                [
                    'logGroupName' => $this->logGroupName,
                    'logStreamName' => $logStreamName,
                ]
            );
        } catch (AwsException $exception) {
            if ($exception->getAwsErrorCode() !== 'ResourceAlreadyExistsException') {
                throw $exception;
            }
        }

        $this->cloudWatchLogsClient->putLogEvents(
            [
                'logGroupName' => $this->logGroupName,
                'logStreamName' => $logStreamName,
                'logEvents' => $logEvents,
            ]
        );

        return count($logEvents);
    }

    private function createLogStreamName(DateTimeImmutable $executedAt): string
    {
        return sprintf(
            '%s-%s-%s-%s',
            self::DEFAULT_LOG_STREAM_PREFIX,
            $this->normalizeLogToken($this->projectName),
            $this->normalizeLogToken($this->environmentName),
            $executedAt->format('Ymd-His-u')
        );
    }

    private function normalizeLogToken(string $token): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9._#\\/-]+/', '-', trim($token));
        if (!is_string($normalized) || $normalized === '') {
            return 'n-a';
        }

        return trim($normalized, '-');
    }

    private function resolveDurationSeconds(RosteringImport $import): ?float
    {
        $startedAt = $import->getStartedAt();
        $finishedAt = $import->getFinishedAt();
        if ($startedAt === null || $finishedAt === null) {
            return null;
        }

        $duration = (float) ($finishedAt->getTimestamp() - $startedAt->getTimestamp());
        if ($duration < 0) {
            return null;
        }

        return $duration;
    }

    private function resolveEventTimestampMs(RosteringImport $import, DateTimeImmutable $defaultTimestamp): int
    {
        $eventTime = $import->getFinishedAt() ?? $import->getStartedAt() ?? $defaultTimestamp;

        return (int) $eventTime->format('Uv');
    }

    private function formatDateTime(?DateTimeInterface $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->format(DATE_ATOM);
    }

    private function resolveOptionValue(string $optionValue, string $defaultValue): string
    {
        $trimmedValue = trim($optionValue);
        if ($trimmedValue !== '') {
            return $trimmedValue;
        }

        return trim($defaultValue);
    }

    /**
     * @param RosteringImport[] $imports
     *
     * @return array<string, string>
     */
    private function resolveImportTypesByReferenceId(array $imports): array
    {
        $importTypes = [];

        foreach ($imports as $import) {
            $referenceId = $import->getReferenceId();
            $importTypes[$referenceId] = $this->detectImportTypeForReferenceId($referenceId);
        }

        return $importTypes;
    }

    private function detectImportTypeForReferenceId(string $referenceId): string
    {
        $header = $this->readHeaderForReferenceId($referenceId);
        if ($header === []) {
            return self::IMPORT_TYPE_UNKNOWN;
        }

        if (in_array(RosteringUserRowValidator::FIELD_USER_USERNAME, $header, true)) {
            return self::IMPORT_TYPE_USERS_CLASSROOMS;
        }

        if (in_array(self::FIELD_PRINCIPAL_USERNAME, $header, true)) {
            return self::IMPORT_TYPE_PRINCIPALS_COLLEGE;
        }

        return self::IMPORT_TYPE_UNKNOWN;
    }

    /**
     * @return string[]
     */
    private function readHeaderForReferenceId(string $referenceId): array
    {
        $stream = null;

        try {
            $stream = $this->fileStorage->read($this->fileKeyResolver->inputFileKey($referenceId));
            $header = fgetcsv(
                $stream,
                0,
                $this->uploadedFileCsvDelimiter,
                $this->uploadedFileCsvEnclosure,
                $this->uploadedFileCsvEscape
            );
            if (!is_array($header)) {
                return [];
            }

            $normalizedHeader = array_map([$this, 'normalizeHeaderColumn'], $header);

            return array_values(array_filter($normalizedHeader, static fn(string $column): bool => $column !== ''));
        } catch (Throwable) {
            return [];
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function normalizeHeaderColumn(string $column): string
    {
        return ltrim(trim($column), "\xEF\xBB\xBF");
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptyMetricBucket(): array
    {
        return [
            'runsTotal' => 0,
            'statusCounts' => [
                RosteringImport::STATUS_PROCESSING => 0,
                RosteringImport::STATUS_PROCESSED => 0,
                RosteringImport::STATUS_FAILED => 0,
            ],
            'totalRows' => 0,
            'processedRows' => 0,
            'failedRows' => 0,
            'attemptsTotal' => 0,
            'durationSum' => 0.0,
            'durationCount' => 0,
            'maxDuration' => 0.0,
        ];
    }
}
