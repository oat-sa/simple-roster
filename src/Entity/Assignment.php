<?php declare(strict_types=1);

namespace App\Entity;

use JsonSerializable;

class Assignment implements JsonSerializable
{
    /**
     * assignment can be taken if other constraints allows it (dates)
     */
    public const STATE_READY = 'ready';

    /**
     * the LTI link for this assignment has been queried, and the state changed as “started” at the same time
     */
    public const STATE_STARTED = 'started';

    /**
     * the test has been completed. We know that it has because simple-roster received the LTI-outcome request from the TAO delivery
     */
    public const STATE_COMPLETED = 'completed';

    /**
     * the assignment cannot be taken anymore
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLineItem(): ?LineItem
    {
        return $this->lineItem;
    }

    public function setLineItem(?LineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->state === self::STATE_CANCELLED;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->getUser()->getUsername(),
            'state' => $this->getState(),
            'lineItem' => $this->lineItem,
        ];
    }
}
