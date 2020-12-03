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
use JsonSerializable;

class UpdateLineItemDto implements JsonSerializable
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ERROR = 'error';
    public const STATUS_IGNORED = 'ignored';

    public const NAME = 'RemoteDeliveryPublicationFinished';

    /** @var string */
    private $id;

    /** @var string */
    private $name;

    /** @var string|null */
    private $slug;

    /** @var string */
    private $lineItemUri;

    /** @var string|null */
    private $status;

    /** @var DateTimeInterface */
    private $triggeredTime;

    public function __construct(
        string $id,
        string $name,
        string $lineItemUri,
        DateTimeInterface $triggeredTime,
        string $slug = null,
        string $status = self::STATUS_IGNORED
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lineItemUri = $lineItemUri;
        $this->triggeredTime = $triggeredTime;
        $this->slug = $slug;
        $this->status = $status;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): ?string
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
}
