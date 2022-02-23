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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Lti\Service;

use OAT\SimpleRoster\Lti\Model\AutogeneratedUser;

class StateDrivenUserGenerator implements UserGeneratorInterface
{
    protected string $lineItemSlug;
    protected string $prefix;
    protected int $indexShift;
    protected ?GroupResolverInterface $groupResolver;

    public function __construct(
        string $lineItemSlug,
        string $prefix,
        int $indexShift,
        ?GroupResolverInterface $groupResolver = null
    ) {
        $this->lineItemSlug = $lineItemSlug;
        $this->prefix = $prefix;
        $this->indexShift = $indexShift;
        $this->groupResolver = $groupResolver;
    }

    public function make(): AutogeneratedUser
    {
        $name = sprintf('%s_%s_%d', $this->lineItemSlug, $this->prefix, $this->indexShift);
        $this->indexShift++;

        $pass = $this->newPassword();

        $group = $this->groupResolver ? $this->groupResolver->resolve() : '';

        return new AutogeneratedUser($name, $pass, $group);
    }

    /**
     * @inheritdoc
     */
    public function makeBatch(int $count): array
    {
        $res = [];
        for ($index = 0; $index < $count; $index++) {
            $res[] = $this->make();
        }

        return $res;
    }

    protected function newPassword(): string
    {
        return substr(str_shuffle('0123456789abcdefghijklmnopqrstvwxyz'), 0, 8);
    }
}
