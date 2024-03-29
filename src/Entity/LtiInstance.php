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

class LtiInstance implements EntityInterface
{
    private int $id;
    private string $label;
    private string $ltiLink;
    private string $ltiKey;
    private string $ltiSecret;

    public function __construct(int $id, string $label, string $ltiLink, string $ltiKey, string $ltiSecret)
    {
        $this->id = $id;
        $this->label = $label;
        $this->ltiLink = $ltiLink;
        $this->ltiKey = $ltiKey;
        $this->ltiSecret = $ltiSecret;
    }

    public function getId(): ?int
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

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setLtiLink(string $ltiLink): void
    {
        $this->ltiLink = $ltiLink;
    }

    public function setLtiKey(string $ltiKey): void
    {
        $this->ltiKey = $ltiKey;
    }

    public function setLtiSecret(string $ltiSecret): void
    {
        $this->ltiSecret = $ltiSecret;
    }
}
