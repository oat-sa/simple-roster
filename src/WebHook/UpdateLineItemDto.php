<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\WebHook;

use DateTimeInterface;
use DateTimeImmutable;
use JsonSerializable;

class UpdateLineItemDto implements JsonSerializable
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ERROR = 'error';
    public const STATUS_IGNORED = 'ignored';

    public const NAME = 'oat\\taoPublishing\\model\\publishing\\event\\RemoteDeliveryCreatedEvent';

    private string $id;
    private string $name;
    private string $slug;
    private string $label;
    private ?int $startAt;
    private ?int $endAt;
    private int $maxAttempts;
    private string $lineItemUri;
    private string $status;
    private DateTimeInterface $triggeredTime;

    public function __construct(
        string $id,
        string $name,
        string $lineItemUri,
        DateTimeInterface $triggeredTime,
        string $slug,
        string $label,
        ?int $startAt,
        ?int $endAt,
        int $maxExecution,
        string $status = self::STATUS_IGNORED
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lineItemUri = $lineItemUri;
        $this->triggeredTime = $triggeredTime;
        $this->slug = $slug;
        $this->status = $status;
        $this->label = $label;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->maxAttempts = $maxExecution;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLineItemUri(): string
    {
        return $this->lineItemUri;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'eventId' => $this->id,
            'status' => $this->status,
        ];
    }

    public function getTriggeredTime(): DateTimeInterface
    {
        return $this->triggeredTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getStartAt(): ?DateTimeInterface
    {
        if ($this->startAt !== null) {
            return (new DateTimeImmutable())->setTimestamp($this->startAt);
        }

        return $this->startAt;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        if ($this->endAt !== null) {
            return (new DateTimeImmutable())->setTimestamp($this->endAt);
        }

        return $this->endAt;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}
