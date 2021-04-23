<?php

/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Entity;

use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\Uid\UuidV6;

class LineItem implements JsonSerializable, EntityInterface
{
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';

    /** @var UuidV6 */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $uri;

    /** @var string */
    private $slug;

    /** @var string */
    private $status;

    /** @var DateTimeInterface|null */
    private $startAt;

    /** @var DateTimeInterface|null */
    private $endAt;

    /** @var int */
    private $maxAttempts;

    /** @var string|null */
    private $groupId;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        UuidV6 $id,
        string $label,
        string $uri,
        string $slug,
        string $status,
        int $maxAttempts = 0,
        string $groupId = null,
        DateTimeInterface $startAt = null,
        DateTimeInterface $endAt = null
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->uri = $uri;
        $this->slug = $slug;

        if (!in_array($status, [self::STATUS_ENABLED, self::STATUS_DISABLED], true)) {
            throw new InvalidArgumentException(sprintf("Invalid line item status received: '%s'", $status));
        }

        $this->status = $status;

        if ($maxAttempts < 0) {
            throw new InvalidArgumentException("'maxAttempts' must be greater or equal to zero.");
        }

        $this->maxAttempts = $maxAttempts;
        $this->groupId = $groupId;

        $this->validateAvailabilityDates($startAt, $endAt);

        $this->startAt = $startAt;
        $this->endAt = $endAt;
    }

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function enable(): self
    {
        $this->status = self::STATUS_ENABLED;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    public function disable(): self
    {
        $this->status = self::STATUS_DISABLED;

        return $this;
    }

    public function getStartAt(): ?DateTimeInterface
    {
        return $this->startAt;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        return $this->endAt;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setAvailabilityDates(DateTimeInterface $startAt = null, DateTimeInterface $endAt = null): self
    {
        $this->validateAvailabilityDates($startAt, $endAt);

        $this->startAt = $startAt;
        $this->endAt = $endAt;

        return $this;
    }

    public function isAvailableForDate(DateTimeInterface $date): bool
    {
        if ($this->startAt !== null && $date < $this->startAt) {
            return false;
        }

        if ($this->endAt !== null && $date > $this->endAt) {
            return false;
        }

        return true;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function hasMaxAttempts(): bool
    {
        return $this->maxAttempts !== 0;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'label' => $this->getLabel(),
            'status' => $this->status,
            'startDateTime' => $this->startAt !== null ? $this->startAt->getTimestamp() : '',
            'endDateTime' => $this->endAt !== null ? $this->endAt->getTimestamp() : '',
            'maxAttempts' => $this->getMaxAttempts(),
            'groupId' => $this->getGroupId()
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateAvailabilityDates(DateTimeInterface $startAt = null, DateTimeInterface $endAt = null): void
    {
        if (null !== $startAt && null !== $endAt && $startAt >= $endAt) {
            throw new InvalidArgumentException(
                "Invalid availability dates received. 'endAt' must be greater than 'startAt'."
            );
        }
    }
}
