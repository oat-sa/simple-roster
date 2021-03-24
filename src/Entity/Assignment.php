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

use Carbon\Carbon;
use DateTime;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use OAT\SimpleRoster\Exception\InvalidAssignmentStatusTransitionException;
use Symfony\Component\Uid\UuidV6;

class Assignment implements JsonSerializable, EntityInterface
{
    public const STATUS_READY = 'ready';
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    private const VALID_STATUSES = [
        self::STATUS_READY,
        self::STATUS_STARTED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** @var UuidV6 */
    private $id;

    /** @var string */
    private $status;

    /** @var User */
    private $user;

    /** @var LineItem */
    private $lineItem;

    /** @var DateTime|null */
    private $updatedAt;

    /** @var int */
    private $attemptsCount;

    public function __construct(
        UuidV6 $id,
        string $status,
        LineItem $lineItem,
        int $attemptsCount = 0,
        DateTime $updatedAt = null
    ) {
        $this->id = $id;

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(sprintf("Invalid assignment status received: '%s'.", $status));
        }

        $this->status = $status;
        $this->lineItem = $lineItem;

        if ($attemptsCount < 0 || ($attemptsCount > $lineItem->getMaxAttempts() && $lineItem->hasMaxAttempts())) {
            throw new InvalidArgumentException("Invalid 'attemptsCount' received.");
        }

        $this->attemptsCount = $attemptsCount;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @throws LogicException
     */
    public function getUser(): User
    {
        if (null === $this->user) {
            throw new LogicException('User is not set.');
        }

        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLineItem(): LineItem
    {
        return $this->lineItem;
    }

    public function setLineItem(LineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function refreshUpdatedAt(): self
    {
        $this->updatedAt = Carbon::now()->toDateTime();

        return $this;
    }

    public function getAttemptsCount(): int
    {
        return $this->attemptsCount;
    }

    /**
     * @throws InvalidAssignmentStatusTransitionException
     */
    public function start(): self
    {
        if ($this->status !== self::STATUS_READY) {
            throw new InvalidAssignmentStatusTransitionException(
                sprintf(
                    "Assignment with id = '%s' cannot be started due to invalid status: '%s' expected, '%s' detected.",
                    $this->id,
                    self::STATUS_READY,
                    $this->status
                )
            );
        }

        if (!$this->lineItem->isEnabled()) {
            throw new InvalidAssignmentStatusTransitionException(
                sprintf(
                    "Assignment with id = '%s' cannot be started, line item is disabled.",
                    $this->id
                )
            );
        }

        if ($this->lineItem->hasMaxAttempts() && $this->attemptsCount >= $this->lineItem->getMaxAttempts()) {
            throw new InvalidAssignmentStatusTransitionException(
                sprintf(
                    "Assignment with id = '%s' cannot be started. Maximum number of attempts (%d) have been reached.",
                    $this->id,
                    $this->lineItem->getMaxAttempts()
                )
            );
        }

        $this->status = self::STATUS_STARTED;
        $this->attemptsCount++;

        return $this;
    }

    /**
     * @throws InvalidAssignmentStatusTransitionException
     */
    public function cancel(): self
    {
        if (!$this->isCancellable()) {
            throw new InvalidAssignmentStatusTransitionException(
                sprintf(
                    "Assignment with id = '%s' cannot be cancelled. Status must be one of '%s', '%s' detected.",
                    $this->id,
                    implode('\', \'', [self::STATUS_READY, self::STATUS_STARTED]),
                    $this->status
                )
            );
        }

        $this->status = self::STATUS_CANCELLED;

        return $this;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_STARTED, self::STATUS_READY], true);
    }

    /**
     * @throws InvalidAssignmentStatusTransitionException
     */
    public function complete(): self
    {
        if ($this->status !== self::STATUS_STARTED) {
            throw new InvalidAssignmentStatusTransitionException(
                sprintf(
                    "Assignment with id = '%s' cannot be completed, because it's in '%s' status, '%s' expected.",
                    $this->id,
                    $this->status,
                    self::STATUS_STARTED
                )
            );
        }
        $maxAttempts = $this->getLineItem()->getMaxAttempts();

        if ($maxAttempts === 0 || $this->getAttemptsCount() < $maxAttempts) {
            $this->status = self::STATUS_READY;

            return $this;
        }

        $this->status = self::STATUS_COMPLETED;

        return $this;
    }

    public function isAvailable(): bool
    {
        return
            $this->lineItem->isEnabled()
            && !$this->isCancelled()
            && $this->lineItem->isAvailableForDate(Carbon::now()->toDateTime());
    }

    private function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => (string)$this->id,
            'username' => $this->getUser()->getUsername(),
            'status' => $this->getStatus(),
            'attemptsCount' => $this->getAttemptsCount(),
            'lineItem' => $this->lineItem,
        ];
    }
}
