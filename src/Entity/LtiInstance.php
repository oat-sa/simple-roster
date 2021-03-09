<?php

/*
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
 *  Copyright (c) 2020 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Entity;

use Carbon\Carbon;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Uid\Uuid;

class LtiInstance implements EntityInterface
{
    /** @var Uuid */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $ltiLink;

    /** @var string */
    private $ltiKey;

    /** @var string */
    private $ltiSecret;

    /** @var int */
    private $createdAt;

    public function __construct(
        Uuid $id,
        string $label,
        string $ltiLink,
        string $ltiKey,
        string $ltiSecret,
        int $createdAt = null
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->ltiLink = $ltiLink;
        $this->ltiKey = $ltiKey;
        $this->ltiSecret = $ltiSecret;
        $this->createdAt = $createdAt ?? (int)(Carbon::now()->getPreciseTimestamp());
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getLtiLink(): string
    {
        return $this->ltiLink;
    }

    public function getLtiKey(): string
    {
        return $this->ltiKey;
    }

    public function getLtiSecret(): string
    {
        return $this->ltiSecret;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable((new DateTime())->setTimestamp($this->createdAt));
    }
}
