<?php

namespace App\Entity;

class Assignment implements EntityInterface
{
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

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getLineItem(): ?LineItem
    {
        return $this->lineItem;
    }

    public function setLineItem(LineItem $lineItem): self
    {
        $this->lineItem = $lineItem;

        return $this;
    }
}
