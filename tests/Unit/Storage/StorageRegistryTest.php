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

namespace OAT\SimpleRoster\Tests\Unit\Storage;

use League\Flysystem\Filesystem;
use LogicException;
use OAT\SimpleRoster\Storage\Exception\StorageNotFoundException;
use OAT\SimpleRoster\Storage\Storage;
use OAT\SimpleRoster\Storage\StorageRegistry;
use PHPUnit\Framework\TestCase;

class StorageRegistryTest extends TestCase
{
    public function testItThrowsExceptionIfStorageIsNotConfigured(): void
    {
        $this->expectException(StorageNotFoundException::class);
        $this->expectExceptionMessage("Storage 'non-existing' is not configured.");

        (new StorageRegistry([]))->getFilesystem('non-existing');
    }

    public function testItThrowsExceptionIfInvalidStorageTypeReceived(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid storage instance type received.');

        new StorageRegistry(['test']);
    }

    public function testItCanResolveFilesystem(): void
    {
        $expectedFilesystem = $this->createMock(Filesystem::class);
        $storage = new Storage('testStorageId', $expectedFilesystem);

        self::assertSame(
            $expectedFilesystem,
            (new StorageRegistry([$storage]))->getFilesystem('testStorageId')
        );
    }

    public function testItReturnsAllStorageIdentifiers(): void
    {
        $storage1 = new Storage('expectedStorageId1', $this->createMock(Filesystem::class));
        $storage2 = new Storage('expectedStorageId2', $this->createMock(Filesystem::class));

        self::assertSame(
            ['expectedStorageId1', 'expectedStorageId2'],
            (new StorageRegistry([$storage1, $storage2]))->getAllStorageIds()
        );
    }
}
