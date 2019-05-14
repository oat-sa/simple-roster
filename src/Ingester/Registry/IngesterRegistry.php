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

use App\Ingester\Ingester\IngesterInterface;
use InvalidArgumentException;

class IngesterRegistry
{
    /** @var IngesterInterface[] */
    private $ingesters = [];

    public function __construct(iterable $ingesters = [])
    {
        foreach ($ingesters as $ingester) {
            $this->add($ingester);
        }
    }

    public function add(IngesterInterface $ingester): self
    {
        $this->ingesters[$ingester->getRegistryItemName()] = $ingester;

        return $this;
    }

    public function get(string $ingesterName): IngesterInterface
    {
        if (!$this->has($ingesterName)) {
            throw new InvalidArgumentException(
                sprintf("Ingester named '%s' cannot be found.", $ingesterName)
            );
        }

        return $this->ingesters[$ingesterName];
    }

    private function has(string $ingesterName): bool
    {
        return isset($this->ingesters[$ingesterName]);
    }

    /**
     * @return IngesterInterface[]
     */
    public function all(): array
    {
        return $this->ingesters;
    }
}
