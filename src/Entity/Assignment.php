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
use JsonSerializable;

class Assignment implements JsonSerializable, EntityInterface
{
    /**
     * Assignment can be taken if other constraints allows it (dates)
     */
    public const STATE_READY = 'ready';

    /**
     * The LTI link for this assignment has been queried, and the state changed as “started” at the same time
     */
    public const STATE_STARTED = 'started';

    /**
     * The test has been completed. We know that it has because simple-roster received the LTI-outcome request from
     * the TAO delivery
     */
    public const STATE_COMPLETED = 'completed';

    /**
     * The assignment cannot be taken anymore
     */
    public const STATE_CANCELLED = 'cancelled';

    /** @var int */
    private $id;

    /** @var string */
    private $state;

    /** @var User */
    private $user;

    /** @var LineItem */
    private $lineItem;

    /** @var DateTime */
    private $updatedAt;

    /** @var int */
    private $attemptsCount = 0;

    /** @var int */
    private $lineItemId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLineItemId(): int
    {
        return $this->lineItemId;
    }

    public function setLineItemId(int $lineItemId): self
    {
        $this->lineItemId = $lineItemId;

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

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function refreshUpdatedAt(): self
    {
        $this->updatedAt = Carbon::now()->toDateTime();

        return $this;
    }

    private function isCancelled(): bool
    {
        return $this->state === self::STATE_CANCELLED;
    }

    public function isCancellable(): bool
    {
        return in_array($this->state, [self::STATE_STARTED, self::STATE_READY], true);
    }

    private function isAvailableForDate(): bool
    {
        return $this->getLineItem()->isAvailableForDate(Carbon::now()->toDateTime());
    }

    public function isAvailable(): bool
    {
        return !$this->isCancelled() && $this->isAvailableForDate();
    }

    public function getAttemptsCount(): int
    {
        return $this->attemptsCount;
    }

    public function setAttemptsCount(int $attemptsCount): self
    {
        $this->attemptsCount = $attemptsCount;

        return $this;
    }

    public function incrementAttemptsCount(): self
    {
        $this->attemptsCount++;

        return $this;
    }

    public function complete(): self
    {
        $maxAttempts = $this->getLineItem()->getMaxAttempts();

        if ($maxAttempts === 0 || $this->getAttemptsCount() < $maxAttempts) {
            return $this->setState(self::STATE_READY);
        }

        return $this->setState(self::STATE_COMPLETED);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->getUser()->getUsername(),
            'state' => $this->getState(),
            'attemptsCount' => $this->getAttemptsCount(),
            'lineItem' => $this->lineItem,
        ];
    }
}
