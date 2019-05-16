<?php declare(strict_types=1);
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

namespace App\Bulk\Operation;

class BulkOperation
{
    public const TYPE_CREATE = 'create';
    public const TYPE_UPDATE = 'update';

    /** @var string */
    private $identifier;

    /** @var string */
    private $type;

    /** @var bool */
    private $isDryRun = false;

    /** @var string[] */
    private $attributes;

    public function __construct(string $identifier, string $type, array $attributes = [])
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->attributes = $attributes;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isDryRun(): bool
    {
        return $this->isDryRun;
    }

    public function setIsDryRun(bool $isDryRun): self
    {
        $this->isDryRun = $isDryRun;

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $attributeName): string
    {
        return $this->attributes[$attributeName];
    }
}
