<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Dto;

use InvalidArgumentException;
use OAT\SimpleRoster\Entity\RosteringImport;

class RosteringImportStatus
{
    private const STATUS_PROCESSED = 'processed';

    public function __construct(
        private readonly string $referenceId,
        private readonly string $status,
        private readonly int $fileLine,
        private readonly array $messages,
        private readonly ?string $resultFileUrl
    ) {
    }

    public static function pending(string $referenceId): self
    {
        return new self($referenceId, 'pending', 0, [], null);
    }

    /**
     * @param array<string, mixed> $result
     */
    public static function fromApiResult(array $result): self
    {
        if (!isset($result['referenceId']) || !is_string($result['referenceId']) || trim($result['referenceId']) === '') {
            throw new InvalidArgumentException('Invalid "referenceId" in Principal Portal status payload.');
        }
        $referenceId = trim($result['referenceId']);

        if (!isset($result['status']) || !is_string($result['status']) || trim($result['status']) === '') {
            throw new InvalidArgumentException('Invalid "status" in Principal Portal status payload.');
        }
        $status = trim($result['status']);

        if (!array_key_exists('fileLine', $result) || (!is_int($result['fileLine']) && !is_numeric($result['fileLine']))) {
            throw new InvalidArgumentException('Invalid "fileLine" in Principal Portal status payload.');
        }
        $fileLine = max(0, (int) $result['fileLine']);

        if (!array_key_exists('messages', $result) || !is_array($result['messages'])) {
            throw new InvalidArgumentException('Invalid "messages" in Principal Portal status payload.');
        }
        $messages = [];
        foreach ($result['messages'] as $message) {
            if (!is_string($message)) {
                throw new InvalidArgumentException('Invalid "messages" in Principal Portal status payload.');
            }

            $messages[] = trim($message);
        }

        if (!array_key_exists('resultFileUrl', $result)) {
            throw new InvalidArgumentException('Invalid "resultFileUrl" in Principal Portal status payload.');
        }

        $resultFileUrl = null;
        if ($result['resultFileUrl'] !== null) {
            if (!is_string($result['resultFileUrl'])) {
                throw new InvalidArgumentException('Invalid "resultFileUrl" in Principal Portal status payload.');
            }

            $trimmedUrl = trim($result['resultFileUrl']);
            $resultFileUrl = $trimmedUrl === '' ? null : $trimmedUrl;
        }

        return new self($referenceId, $status, $fileLine, $messages, $resultFileUrl);
    }

    public static function fromImport(RosteringImport $import, ?string $resultFileUrl): self
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
            $messages,
            $resultFileUrl
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

    public function getResultFileUrl(): ?string
    {
        return $this->resultFileUrl;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function withResultFileUrl(?string $resultFileUrl): self
    {
        return new self(
            $this->referenceId,
            $this->status,
            $this->fileLine,
            $this->messages,
            $resultFileUrl
        );
    }

    /**
     * @return array{referenceId: string, status: string, fileLine: int, messages: array<int, string>, resultFileUrl: ?string}
     */
    public function toArray(): array
    {
        return [
            'referenceId' => $this->referenceId,
            'status' => $this->status,
            'fileLine' => $this->fileLine,
            'messages' => $this->messages,
            'resultFileUrl' => $this->resultFileUrl,
        ];
    }
}
