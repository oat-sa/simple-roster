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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Service\AwsS3;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use OAT\SimpleRoster\Service\AwsS3\FolderSyncService;
use PHPUnit\Framework\TestCase;

class FolderSyncServiceTest extends TestCase
{
    public function testSync(): void
    {
        $service = new FolderSyncService(
            $source = new Filesystem(new InMemoryFilesystemAdapter()),
            $dest   = new Filesystem(new InMemoryFilesystemAdapter())
        );

        $dir = 'some_dir';

        $path1 = $this->path($dir, 'path/one.txt');
        $source->write($path1, $content1 = 'some_data1');

        $path2 = $this->path($dir, 'path/two/file.txt');
        $source->write($path2, $content2 = 'some_data2');

        $service->sync($dir);

        $this->assertTrue($dest->fileExists($path1));
        $this->assertTrue($dest->fileExists($path2));
        $this->assertSame($content1, $dest->read($path1));
        $this->assertSame($content2, $dest->read($path2));
    }

    public function testOverrideForExistedFile(): void
    {
        $service = new FolderSyncService(
            $source = new Filesystem(new InMemoryFilesystemAdapter()),
            $dest   = new Filesystem(new InMemoryFilesystemAdapter())
        );

        $dir = 'some_dir';

        $path1 = $this->path($dir, 'path/one.txt');
        $source->write($path1, $content1 = 'some_data1');

        $path2 = $this->path($dir, 'path/two/file.txt');
        $source->write($path2, $content2 = 'some_data2');

        $dest->write($path1, 'some_old_data1');

        $service->sync($dir);

        $this->assertTrue($dest->fileExists($path1));
        $this->assertTrue($dest->fileExists($path2));
        $this->assertSame($content1, $dest->read($path1));
        $this->assertSame($content2, $dest->read($path2));
    }

    protected function path(string $root, string $path): string
    {
        return trim($root, '/') . '/' . trim($path, '/');
    }
}
