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

namespace OAT\SimpleRoster\Lti\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OAT\SimpleRoster\Entity\LtiInstance;
use OutOfBoundsException;
use Traversable;

class UniqueLtiInstanceCollection implements Countable, IteratorAggregate
{
    /** @var LtiInstance[] */
    private $ltiInstances = [];

    public function __construct(LtiInstance ...$ltiInstances)
    {
        foreach ($ltiInstances as $ltiInstance) {
            $this->add($ltiInstance);
        }
    }

    public function add(LtiInstance $ltiInstance): self
    {
        if (!$this->contains($ltiInstance)) {
            $this->ltiInstances[] = $ltiInstance;
        }

        return $this;
    }

    /**
     * @throws OutOfBoundsException
     */
    public function getByIndex(int $index): LtiInstance
    {
        if ($index < 0 || $index >= count($this)) {
            throw new OutOfBoundsException(
                sprintf(
                    'Invalid index received: %d, possible range: 0..%d',
                    $index,
                    count($this) - 1
                )
            );
        }

        return $this->ltiInstances[$index];
    }

    public function filterByLtiKey(string $ltiKey): self
    {
        $this->ltiInstances = array_filter(
            $this->ltiInstances,
            static function (LtiInstance $ltiInstance) use ($ltiKey): bool {
                return $ltiInstance->getLtiKey() === $ltiKey;
            }
        );

        return $this;
    }

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->ltiInstances);
    }

    public function count(): int
    {
        return count($this->ltiInstances);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    private function contains(LtiInstance $ltiInstance): bool
    {
        /** @var LtiInstance $instance */
        foreach ($this as $instance) {
            if ($instance->getLabel() === $ltiInstance->getLabel()) {
                return true;
            }
        }

        return false;
    }
}
