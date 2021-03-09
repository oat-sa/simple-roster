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

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

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

    public function __construct(UuidV6 $id, string $label, string $ltiLink, string $ltiKey, string $ltiSecret)
    {
        $this->id = $id;
        $this->label = $label;
        $this->ltiLink = $ltiLink;
        $this->ltiKey = $ltiKey;
        $this->ltiSecret = $ltiSecret;
    }

    public function getId(): UuidV6
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
}
