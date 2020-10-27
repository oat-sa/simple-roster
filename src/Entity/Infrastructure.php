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

class Infrastructure implements EntityInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $label;

    /** @var string */
    private $ltiDirectorLink;

    /** @var string */
    private $ltiKey;

    /** @var string */
    private $ltiSecret;

    public function getId(): int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLtiDirectorLink(): string
    {
        return $this->ltiDirectorLink;
    }

    public function setLtiDirectorLink(string $ltiDirectorLink): self
    {
        $this->ltiDirectorLink = $ltiDirectorLink;

        return $this;
    }

    public function setLtiKey(string $ltiKey): self
    {
        $this->ltiKey = $ltiKey;

        return $this;
    }

    public function setLtiSecret(string $ltiSecret): self
    {
        $this->ltiSecret = $ltiSecret;

        return $this;
    }
}
