<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Entity;

use DateTimeInterface;

class RosteringImport implements EntityInterface
{
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    private ?int $id = null;
    private string $referenceId;
    private string $status;
    private ?string $errorMessage = null;
    private int $attempts = 0;
    private ?int $totalRows = null;
    private ?int $processedRows = null;
    private ?int $failedRows = null;
    private ?DateTimeInterface $startedAt = null;
    private ?DateTimeInterface $finishedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function setReferenceId(string $referenceId): self
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    public function setTotalRows(?int $totalRows): self
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    public function getProcessedRows(): ?int
    {
        return $this->processedRows;
    }

    public function setProcessedRows(?int $processedRows): self
    {
        $this->processedRows = $processedRows;

        return $this;
    }

    public function getFailedRows(): ?int
    {
        return $this->failedRows;
    }

    public function setFailedRows(?int $failedRows): self
    {
        $this->failedRows = $failedRows;

        return $this;
    }

    public function getStartedAt(): ?DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeInterface $finishedAt): self
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }
}
