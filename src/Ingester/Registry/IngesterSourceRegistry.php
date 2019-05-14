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

namespace App\Ingester\Registry;

use App\Ingester\Source\IngesterSourceInterface;
use InvalidArgumentException;

class IngesterSourceRegistry
{
    /** @var IngesterSourceInterface[] */
    private $sources = [];

    public function __construct(iterable $sources = [])
    {
        foreach ($sources as $source) {
            $this->add($source);
        }
    }

    public function add(IngesterSourceInterface $source): self
    {
        $this->sources[$source->getRegistryItemName()] = $source;

        return $this;
    }

    public function get(string $sourceName): IngesterSourceInterface
    {
        if (!$this->has($sourceName)) {
            throw new InvalidArgumentException(
                sprintf("Ingester source named '%s' cannot be found.", $sourceName)
            );
        }

        return $this->sources[$sourceName];
    }

    private function has(string $sourceName): bool
    {
        return isset($this->sources[$sourceName]);
    }

    /**
     * @return IngesterSourceInterface[]
     */
    public function all(): array
    {
        return $this->sources;
    }
}
