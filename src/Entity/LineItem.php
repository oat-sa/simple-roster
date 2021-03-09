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
use JsonSerializable;
use Symfony\Component\Uid\UuidV6;

class LineItem implements JsonSerializable, EntityInterface
{
    /** @var UuidV6 */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $uri;

    /** @var string */
    private $slug;

    /** @var DateTimeInterface|null */
    private $startAt;

    /** @var DateTimeInterface|null */
    private $endAt;

    /** @var int */
    private $maxAttempts = 0;

    /** @var bool */
    private $isActive = true;

    public function getId(): UuidV6
    {
        return $this->id;
    }

    public function setId(UuidV6 $id): self
    {
        $this->id = $id;

        return $this;
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

    public function setUri(string $uri): self
    {
        $this->uri = $uri;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getStartAt(): ?DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(?DateTimeInterface $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?DateTimeInterface $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function isAvailableForDate(DateTimeInterface $date): bool
    {
        if (null === $this->startAt || null === $this->endAt) {
            return true;
        }

        return $this->startAt <= $date && $this->endAt >= $date;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(int $maxAttempts): self
    {
        $this->maxAttempts = $maxAttempts;

        return $this;
    }

    public function hasMaxAttempts(): bool
    {
        return $this->maxAttempts !== 0;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'label' => $this->getLabel(),
            'isActive' => $this->isActive(),
            'startDateTime' => $this->getStartAt() !== null ? $this->getStartAt()->getTimestamp() : '',
            'endDateTime' => $this->getEndAt() !== null ? $this->getEndAt()->getTimestamp() : '',
            'maxAttempts' => $this->getMaxAttempts(),
        ];
    }
}
