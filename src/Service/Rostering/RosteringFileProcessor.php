<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering;

use Doctrine\DBAL\Connection;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Writer;
use InvalidArgumentException;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Entity\User;
use OAT\SimpleRoster\Repository\AssignmentRepository;
use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\Repository\RosteringImportRepository;
use OAT\SimpleRoster\Repository\UserRepository;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringUserEntryDto;
use OAT\SimpleRoster\Service\Rostering\Dto\RosteringUserEntryDtoFactory;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;
use OAT\SimpleRoster\Service\Rostering\Validation\RosteringUserRowValidator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Exception\UnrecoverableExceptionInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Throwable;

class RosteringFileProcessor
{
    private const RESULT_STATUS = 'status';
    private const RESULT_ERROR_TYPE = 'errorType';
    private const RESULT_ERROR_CODE = 'errorCode';
    private const RESULT_ERROR_MESSAGE = 'errorMessage';

    private const ROW_STATUS_PROCESSED = 'processed';
    private const ROW_STATUS_VALIDATION_FAILED = '400';
    private const ROW_STATUS_INTERNAL_ERROR = '500';

    private const ERROR_TYPE = 'error';
    private const ERROR_CODE_VALIDATION = 'validation.fieldError';
    private const ERROR_CODE_INTERNAL = 'csv.import.internalError';
    private const ERROR_MESSAGE_INTERNAL = 'Internal error while processing row.';

    private const MAX_REFERENCE_ID_LENGTH = 255;

    private const ASSIGNMENT_STATE_READY = 'ready';
    /**
     * @var array<string, int>
     */
    private array $lineItemIdsBySessionName = [];

    public function __construct(
        private readonly FileStorageInterface $fileStorage,
        private readonly Connection $connection,
        private readonly UserRepository $userRepository,
        private readonly LineItemRepository $lineItemRepository,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly RosteringImportRepository $rosteringImportRepository,
        private readonly RosteringFileKeyResolver $fileKeyResolver,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
        private readonly RosteringUserEntryDtoFactory $entryDtoFactory,
        private readonly RosteringUserCacheSynchronizer $userCacheSynchronizer,
        private readonly SeekableStreamFactory $seekableStreamFactory
    ) {
    }

    public function process(string $referenceId): void
    {
        $this->lineItemIdsBySessionName = [];
        $this->userCacheSynchronizer->reset();

        $referenceId = trim($referenceId);
        $this->validateReferenceId($referenceId);

        $inputFileKey = $this->fileKeyResolver->inputFileKey($referenceId);
        $outputFileKey = $this->fileKeyResolver->outputFileKey($referenceId);
        $inputStream = null;
        $inputCsvStream = null;
        $resultStream = null;

        $totalRows = 0;
        $failedRows = 0;
        $importableRows = 0;

        $this->rosteringImportRepository->markProcessing($referenceId);

        try {
            $inputStream = $this->fileStorage->read($inputFileKey);
            $resultStream = fopen('php://temp', 'rb+');

            if (false === $resultStream) {
                throw new RuntimeException('Unable to create temporary stream for rostering result file.');
            }

            $inputCsvStream = $this->seekableStreamFactory->create($inputStream, 'rostering input file');

            $reader = Reader::from($inputCsvStream);
            $reader->setHeaderOffset(0);
            $header = $reader->getHeader();
            $rows = (new Statement())->process($reader)->getRecords();

            $resultHeader = $this->buildResultHeader($header);
            $writer = Writer::from($resultStream);
            $writer->insertOne($resultHeader);

            foreach ($rows as $row) {
                $wasImportable = false;
                $resultRow = $this->processRow($row, $wasImportable);
                if (null === $resultRow) {
                    continue;
                }

                ++$totalRows;

                if ($wasImportable) {
                    ++$importableRows;
                }

                if ($resultRow[self::RESULT_STATUS] !== self::ROW_STATUS_PROCESSED) {
                    ++$failedRows;
                }

                $writer->insertOne($this->toCsvLine($resultHeader, $resultRow));
            }

            if ($importableRows === 0) {
                $this->logger->info(
                    sprintf(
                        "Rostering file '%s' has no importable SR rows; writing pass-through result output.",
                        $referenceId
                    ),
                    [
                        'referenceId' => $referenceId,
                        'inputFileKey' => $inputFileKey,
                        'outputFileKey' => $outputFileKey,
                        'totalRows' => $totalRows,
                    ]
                );
            }

            rewind($resultStream);
            $this->fileStorage->store($resultStream, $outputFileKey);
            $this->rosteringImportRepository->markProcessed($referenceId, $totalRows, $failedRows);
        } catch (Throwable $exception) {
            $this->markImportFailure($referenceId, $exception, $totalRows, $failedRows);

            if ($exception instanceof UnrecoverableExceptionInterface) {
                throw $exception;
            }

            throw new RuntimeException(
                sprintf('Unable to process rostering file "%s".', $inputFileKey),
                0,
                $exception
            );
        } finally {
            if (is_resource($inputStream)) {
                fclose($inputStream);
            }

            if (is_resource($inputCsvStream)) {
                fclose($inputCsvStream);
            }

            if (is_resource($resultStream)) {
                fclose($resultStream);
            }
        }

        $this->userCacheSynchronizer->synchronize();

        $this->logger->info(
            sprintf("Rostering file '%s' processed.", $referenceId),
            [
                'referenceId' => $referenceId,
                'inputFileKey' => $inputFileKey,
                'outputFileKey' => $outputFileKey,
                'totalRows' => $totalRows,
                'importableRows' => $importableRows,
                'failedRows' => $failedRows,
            ]
        );
    }

    /**
     * @param array<string> $header
     *
     * @return array<string>
     */
    private function buildResultHeader(array $header): array
    {
        return array_values(
            array_unique(
                array_merge(
                    $header,
                    [
                        self::RESULT_STATUS,
                        self::RESULT_ERROR_TYPE,
                        self::RESULT_ERROR_CODE,
                        self::RESULT_ERROR_MESSAGE,
                    ]
                )
            )
        );
    }

    /**
     * @param array<string, string|null> $row
     *
     * @return array<string, string>|null
     */
    private function processRow(array $row, bool &$wasImportable): ?array
    {
        $normalizedRow = $this->normalizeRow($row);

        if ($this->isRowEmpty($normalizedRow)) {
            $wasImportable = false;

            return null;
        }

        $resultRow = $normalizedRow;
        $resultRow[self::RESULT_STATUS] = self::ROW_STATUS_PROCESSED;
        $resultRow[self::RESULT_ERROR_TYPE] = '';
        $resultRow[self::RESULT_ERROR_CODE] = '';
        $resultRow[self::RESULT_ERROR_MESSAGE] = '';

        try {
            $entryDto = $this->entryDtoFactory->fromArray($normalizedRow);

            $wasImportable = $entryDto->isImportable();
            if (!$wasImportable) {
                return $resultRow;
            }

            $this->runInTransaction(function () use ($entryDto): void {
                $this->upsertUser($entryDto);
            });
        } catch (RosteringValidationException $exception) {
            $wasImportable = true;
            $resultRow[self::RESULT_STATUS] = self::ROW_STATUS_VALIDATION_FAILED;
            $resultRow[self::RESULT_ERROR_TYPE] = self::ERROR_TYPE;
            $resultRow[self::RESULT_ERROR_CODE] = self::ERROR_CODE_VALIDATION;
            $resultRow[self::RESULT_ERROR_MESSAGE] = $exception->getMessage();
        } catch (Throwable $exception) {
            $wasImportable = true;
            $resultRow[self::RESULT_STATUS] = self::ROW_STATUS_INTERNAL_ERROR;
            $resultRow[self::RESULT_ERROR_TYPE] = self::ERROR_TYPE;
            $resultRow[self::RESULT_ERROR_CODE] = self::ERROR_CODE_INTERNAL;
            $resultRow[self::RESULT_ERROR_MESSAGE] = self::ERROR_MESSAGE_INTERNAL;

            $this->logger->warning(
                'Unexpected error while importing rostering row.',
                ['exception' => $exception]
            );
        }

        return $resultRow;
    }

    private function upsertUser(RosteringUserEntryDto $entryDto): void
    {
        $username = $entryDto->getUserUsername();
        if (null === $username) {
            throw new InvalidArgumentException(
                sprintf('Expected validated %s with non-empty username.', RosteringUserEntryDto::class)
            );
        }

        $password = $entryDto->getUserPassword() ?? '';
        $organizationId = $entryDto->getUserOrganizationId() ?? '';
        $sessionName = $entryDto->getSessionName() ?? '';

        $isUserActive = $entryDto->getUserActive();
        $userId = $this->userRepository->findIdByUsername($username);

        if ($isUserActive === false) {
            if (null === $userId) {
                return;
            }

            $this->assignmentRepository->deleteByUserId($userId);
            $this->userRepository->deleteById($userId);
            $this->userCacheSynchronizer->markForInvalidationOnly($username);

            return;
        }

        $hasPassword = '' !== $password;
        $hasOrganizationId = '' !== $organizationId;
        $hasSessionName = '' !== $sessionName;

        if (null !== $userId) {
            $hasUserChanged = false;
            $hasAssignmentChanged = false;
            $fieldsToUpdate = [];

            if ($hasPassword) {
                $fieldsToUpdate['password'] = $this->hashUserPassword($username, $password);
            }

            if ($hasOrganizationId) {
                $fieldsToUpdate['groupId'] = $organizationId;
            }

            $this->userRepository->updateForRostering($username, $fieldsToUpdate);
            $hasUserChanged = $fieldsToUpdate !== [];

            if ($hasSessionName) {
                $this->replaceUserAssignment($userId, $sessionName);
                $hasAssignmentChanged = true;
            }

            if ($hasUserChanged || $hasAssignmentChanged) {
                $this->userCacheSynchronizer->markForWarmup($username);
            }

            return;
        }

        if (!$hasPassword) {
            throw new RosteringValidationException(
                sprintf('Field "%s" is required for new user.', RosteringUserRowValidator::FIELD_USER_PASSWORD)
            );
        }

        if (!$hasOrganizationId) {
            throw new RosteringValidationException(
                sprintf('Field "%s" is required for new user.', RosteringUserRowValidator::FIELD_USER_ORGANIZATION_ID)
            );
        }

        if (!$hasSessionName) {
            throw new RosteringValidationException(
                sprintf('Field "%s" is required for new user.', RosteringUserRowValidator::FIELD_SESSION_NAME)
            );
        }

        $createdUserId = $this->createUser($username, $password, $organizationId);
        $this->replaceUserAssignment($createdUserId, $sessionName);
        $this->userCacheSynchronizer->markForWarmup($username);
    }

    private function createUser(string $username, string $password, string $organizationId): int
    {
        $passwordHash = $this->hashUserPassword($username, $password);

        $userId = $this->userRepository->insertForRostering($username, $passwordHash, $organizationId);
        if (0 === $userId) {
            throw new RuntimeException(sprintf('Unable to create user "%s".', $username));
        }

        return $userId;
    }

    private function hashUserPassword(string $username, string $plainPassword): string
    {
        $user = (new User())->setUsername($username);

        return $this->passwordHasher->hashPassword($user, $plainPassword);
    }

    /**
     * @param array<string, string> $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ('' !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string|null> $row
     *
     * @return array<string, string>
     */
    private function normalizeRow(array $row): array
    {
        $normalizedRow = [];

        foreach ($row as $column => $value) {
            if ($value === null) {
                $normalizedRow[$column] = '';
                continue;
            }

            $normalizedRow[$column] = is_string($value) ? trim($value) : (string)$value;
        }

        return $normalizedRow;
    }

    private function replaceUserAssignment(int $userId, string $sessionName): void
    {
        $lineItemId = $this->findLineItemIdBySessionName($sessionName);
        if (null === $lineItemId) {
            throw new RosteringValidationException(
                sprintf(
                    'Line item "%s" does not exist for field "%s".',
                    $sessionName,
                    RosteringUserRowValidator::FIELD_SESSION_NAME
                )
            );
        }

        $this->assignmentRepository->replaceForRostering(
            $userId,
            $lineItemId,
            self::ASSIGNMENT_STATE_READY
        );
    }

    private function findLineItemIdBySessionName(string $sessionName): ?int
    {
        if (isset($this->lineItemIdsBySessionName[$sessionName])) {
            return $this->lineItemIdsBySessionName[$sessionName];
        }

        $lineItem = $this->lineItemRepository->findOneBy(['slug' => $sessionName]);
        if (!$lineItem instanceof LineItem) {
            return null;
        }

        $lineItemId = (int)$lineItem->getId();
        $this->lineItemIdsBySessionName[$sessionName] = $lineItemId;

        return $lineItemId;
    }

    private function runInTransaction(callable $operation): void
    {
        $this->connection->beginTransaction();

        try {
            $operation();
            $this->connection->commit();
        } catch (Throwable $exception) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    private function validateReferenceId(string $referenceId): void
    {
        if ($referenceId === '') {
            throw new UnrecoverableMessageHandlingException('Reference ID cannot be empty.');
        }

        if (strlen($referenceId) > self::MAX_REFERENCE_ID_LENGTH) {
            throw new UnrecoverableMessageHandlingException(
                sprintf('Reference ID exceeds max length (%d).', self::MAX_REFERENCE_ID_LENGTH)
            );
        }

        if (preg_match('/^[A-Za-z0-9._-]+$/', $referenceId) !== 1 || str_contains($referenceId, '..')) {
            throw new UnrecoverableMessageHandlingException('Reference ID contains unsupported characters.');
        }
    }

    /**
     * @param array<string> $header
     * @param array<string, string> $row
     *
     * @return array<string>
     */
    private function toCsvLine(array $header, array $row): array
    {
        $line = [];

        foreach ($header as $column) {
            $line[] = $row[$column] ?? '';
        }

        return $line;
    }

    private function markImportFailure(
        string $referenceId,
        Throwable $exception,
        int $totalRows,
        int $failedRows
    ): void {
        try {
            $this->rosteringImportRepository->markFailed($referenceId, $exception->getMessage(), $totalRows, $failedRows);
        } catch (Throwable $trackingException) {
            $this->logger->error(
                sprintf('Unable to persist failed status for rostering import "%s".', $referenceId),
                [
                    'referenceId' => $referenceId,
                    'trackingError' => $trackingException->getMessage(),
                    'trackingTrace' => $trackingException->getTraceAsString(),
                ]
            );
        }
    }

}
