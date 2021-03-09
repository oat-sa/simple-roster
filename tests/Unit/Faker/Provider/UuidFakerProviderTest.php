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
    public function testItCanGenerateValidUuidV4Identifier(): void
    {
        self::assertTrue(Uuid::isValid((string)UuidFakerProvider::uuidV4()));
    }

    public function testItCanGenerateValidCustomUuidV4Identifier(): void
    {
        $customUuid = UuidFakerProvider::customUuidV4('00000000-0000-4000-0000-000000000001');

        self::assertTrue(Uuid::isValid((string)$customUuid));
    }

    public function testItThrowsExceptionIfInvalidCustomUuidV4IdentifierIsProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UuidFakerProvider::customUuidV4('invalid');
    }
}
