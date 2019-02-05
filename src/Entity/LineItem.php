<?php declare(strict_types=1);

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JsonSerializable;

class LineItem implements JsonSerializable
{
    /** @var int */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $uri;

    /** @var DateTime */
    private $startAt;

    /** @var DateTime */
    private $endAt;

    /** @var Infrastructure */
    private $infrastructure;

    /** @var ArrayCollection|Assignment[] */
    private $assignments;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getInfrastructure(): ?Infrastructure
    {
        return $this->infrastructure;
    }

    public function setInfrastructure(Infrastructure $infrastructure): self
    {
        $this->infrastructure = $infrastructure;

        return $this;
    }

    /**
     * @return Collection|Assignment[]
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments[] = $assignment;
            $assignment->setLineItem($this);
        }

        return $this;
    }

    public function removeAssignment(Assignment $assignment): self
    {
        if ($this->assignments->contains($assignment)) {
            $this->assignments->removeElement($assignment);
            // set the owning side to null (unless already changed)
            if ($assignment->getLineItem() === $this) {
                $assignment->setLineItem(null);
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'label' => $this->getLabel(),
            'startDateTime' => $this->getStartAt() ? $this->getStartAt()->getTimestamp() : '',
            'endDateTime' => $this->getEndAt() ? $this->getEndAt()->getTimestamp() : '',
            'infrastructureId' => $this->getInfrastructure()->getId(),
        ];
    }
}
