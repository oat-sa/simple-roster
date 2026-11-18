<?php

declare(strict_types=1);

namespace OAT\SimpleRoster\Service\Rostering\Dto;

use DateTimeImmutable;
use DateTimeInterface;
use OAT\SimpleRoster\Service\Rostering\Exception\RosteringValidationException;

final readonly class RosteringFileProcessingContext
{
    private const MAX_REFERENCE_ID_LENGTH = 255;

    private function __construct(
        private string $referenceId,
        private ?DateTimeImmutable $uploadedAt
    ) {
    }

    public static function fromPayload(string $referenceId, ?string $uploadedAt = null): self
    {
        $referenceId = trim($referenceId);
        self::validateReferenceId($referenceId);

        return new self($referenceId, self::parseUploadedAt($uploadedAt));
    }

    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    public function getUploadedAt(): ?DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    private static function validateReferenceId(string $referenceId): void
    {
        if ('' === $referenceId) {
            throw new RosteringValidationException('Reference ID cannot be empty.');
        }

        if (strlen($referenceId) > self::MAX_REFERENCE_ID_LENGTH) {
            throw new RosteringValidationException(
                sprintf('Reference ID exceeds max length (%d).', self::MAX_REFERENCE_ID_LENGTH)
            );
        }

        if (preg_match('/^[A-Za-z0-9._-]+$/', $referenceId) !== 1 || str_contains($referenceId, '..')) {
            throw new RosteringValidationException('Reference ID contains unsupported characters.');
        }
    }

    private static function parseUploadedAt(?string $uploadedAt): ?DateTimeImmutable
    {
        if (null === $uploadedAt) {
            return null;
        }

        $parsedUploadedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $uploadedAt);
        if (
            !$parsedUploadedAt instanceof DateTimeImmutable
            || $parsedUploadedAt->format(DateTimeInterface::ATOM) !== $uploadedAt
        ) {
            throw new RosteringValidationException('Uploaded at must be a valid DATE_ATOM timestamp.');
        }

        return $parsedUploadedAt;
    }
}
