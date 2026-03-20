<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Dto;

use OAT\SimpleRoster\Entity\RosteringImport;

class RosteringImportStatus
{
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
