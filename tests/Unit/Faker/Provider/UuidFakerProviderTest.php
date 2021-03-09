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

namespace OAT\SimpleRoster\Tests\Unit\Faker\Provider;

use InvalidArgumentException;
use OAT\SimpleRoster\Faker\Provider\UuidFakerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UuidFakerProviderTest extends TestCase
{
    public function testItCanGenerateValidUuidV6Identifier(): void
    {
        self::assertTrue(Uuid::isValid((string)UuidFakerProvider::uuidV6()));
    }

    public function testItCanGenerateValidCustomUuidV6Identifier(): void
    {
        $customUuid = UuidFakerProvider::uuidV6('00000001-0000-6000-0000-000000000000');

        self::assertTrue(Uuid::isValid((string)$customUuid));
    }

    public function testItThrowsExceptionIfInvalidCustomUuidV6IdentifierIsProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UuidFakerProvider::uuidV6('invalid');
    }
}
