<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Dto;

use InvalidArgumentException;
use OAT\SimpleRoster\Entity\RosteringImport;

class RosteringImportStatus
{
    private const string STATUS_PROCESSED = 'processed';

    public function __construct(
        private readonly string $referenceId,
        private readonly string $status,
        private readonly int $fileLine,
        private readonly array $messages
    ) {
    }

    public static function pending(string $referenceId): self
    {
        return new self($referenceId, 'pending', 0, []);
    }

    /**
     * @param array<string, mixed> $result
     */
    public static function fromApiResult(array $result, string $referenceId): self
    {
        if (!isset($result['status']) || !is_string($result['status']) || trim($result['status']) === '') {
            throw new InvalidArgumentException('Invalid "status" in external reporting system status payload.');
        }
        $status = trim($result['status']);

        if (!array_key_exists('fileLine', $result) || (!is_int($result['fileLine']) && !is_numeric($result['fileLine']))) {
            throw new InvalidArgumentException('Invalid "fileLine" in external reporting system status payload.');
        }
        $fileLine = max(0, (int) $result['fileLine']);

        if (!array_key_exists('messages', $result) || !is_array($result['messages'])) {
            throw new InvalidArgumentException('Invalid "messages" in external reporting system status payload.');
        }
        $messages = [];
        foreach ($result['messages'] as $message) {
            if (!is_string($message)) {
                throw new InvalidArgumentException('Invalid "messages" in external reporting system status payload.');
            }

            $messages[] = trim($message);
        }

        return new self($referenceId, $status, $fileLine, $messages);
    }

    public static function fromImport(RosteringImport $import): self
    {
        $messages = [];
        $errorMessage = $import->getErrorMessage();
        if ($errorMessage !== null && trim($errorMessage) !== '') {
            $messages[] = $errorMessage;
        }

        return new self(
            $import->getReferenceId(),
            $import->getStatus(),
            $import->getTotalRows() ?? 0,
            $messages
        );
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFileLine(): int
    {
        return $this->fileLine;
    }

    /**
     * @return array<int, string>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * @return array{status: string, fileLine: int, messages: array<int, string>}
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'fileLine' => $this->fileLine,
            'messages' => $this->messages,
        ];
    }
}
