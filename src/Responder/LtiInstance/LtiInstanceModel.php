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

namespace OAT\SimpleRoster\Responder\LtiInstance;

use OAT\SimpleRoster\Entity\LtiInstance;

class LtiInstanceModel implements \JsonSerializable
{
    private int $id;
    private string $label;
    private string $ltiLink;

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function setLtiLink(string $ltiLink): self
    {
        $this->ltiLink = $ltiLink;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'lti_link' => $this->ltiLink
        ];
    }

    public function fillFromEntity(LtiInstance $entity): self
    {
        $this->id = (int)$entity->getId();
        $this->ltiLink = $entity->getLtiLink();
        $this->label = $entity->getLabel();

        return $this;
    }
}
