<?php

declare(strict_types=1);

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

namespace App\Entity;

use DateTimeInterface;
use JsonSerializable;

class LineItem implements JsonSerializable, EntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $uri;

    /** @var string */
    private $slug;

    /** @var DateTimeInterface */
    private $startAt;

    /** @var DateTimeInterface */
    private $endAt;

    /** @var Infrastructure */
    private $infrastructure;

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

    public function getInfrastructure(): Infrastructure
    {
        return $this->infrastructure;
    }

    public function setInfrastructure(Infrastructure $infrastructure): self
    {
        $this->infrastructure = $infrastructure;

        return $this;
    }

    public function isAvailableForDate(DateTimeInterface $date): bool
    {
        if (null === $this->startAt || null === $this->endAt) {
            return true;
        }

        return $this->startAt <= $date && $this->endAt >= $date;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'label' => $this->getLabel(),
            'startDateTime' => $this->getStartAt() ? $this->getStartAt()->getTimestamp() : '',
            'endDateTime' => $this->getEndAt() ? $this->getEndAt()->getTimestamp() : '',
            'infrastructure' => $this->getInfrastructure()->getId(),
        ];
    }
}
