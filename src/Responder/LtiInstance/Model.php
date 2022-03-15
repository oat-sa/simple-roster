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

class Model implements \JsonSerializable
{
    private int $id;
    private string $label;
    private string $ltiLink;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLtiLink(): string
    {
        return $this->ltiLink;
    }

    /**
     * @param string $ltiLink
     */
    public function setLtiLink(string $ltiLink): void
    {
        $this->ltiLink = $ltiLink;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'lti_link' => $this->ltiLink
        ];
    }

    public static function fromEntity(LtiInstance $entity): Model
    {
        $obj = new static();
        $obj->id = $entity->getId();
        $obj->ltiLink = $entity->getLtiLink();
        $obj->label = $entity->getLabel();

        return $obj;
    }
}