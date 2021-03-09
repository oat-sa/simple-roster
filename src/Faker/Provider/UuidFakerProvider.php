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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Faker\Provider;

use Faker\Provider\Base as BaseProvider;
use InvalidArgumentException;
use Symfony\Component\Uid\UuidV4;

class UuidFakerProvider extends BaseProvider
{
    public static function uuidV4(): UuidV4
    {
        return new UuidV4();
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function customUuidV4(string $customUuid): UuidV4
    {
        return new UuidV4($customUuid);
    }
}
