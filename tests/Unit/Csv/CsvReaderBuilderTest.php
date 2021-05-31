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

namespace OAT\SimpleRoster\Tests\Unit\Csv;

use League\Flysystem\Filesystem;
use OAT\SimpleRoster\Csv\CsvReaderBuilder;
use OAT\SimpleRoster\Csv\Exception\StreamResourceNotFoundException;
use OAT\SimpleRoster\Storage\StorageRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CsvReaderBuilderTest extends TestCase
{
    /** @var Filesystem|MockObject */
    private $filesystem;

    /** @var StorageRegistry|MockObject */
    private $storageRegistry;

    /** @var CsvReaderBuilder */
    private CsvReaderBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->createMock(Filesystem::class);
        $this->storageRegistry = $this->createMock(StorageRegistry::class);

        $this->subject = new CsvReaderBuilder($this->storageRegistry);
    }

    public function testItThrowsExceptionIfResourceNotFound(): void
    {
        $this->expectException(StreamResourceNotFoundException::class);
        $this->expectExceptionMessage("Resource not found: 'non-existing.csv'");

        (new CsvReaderBuilder($this->createMock(StorageRegistry::class)))
            ->build('non-existing.csv');
    }

    public function testIfDelimiterCanBeOverridden(): void
    {
        $this->prepareFilesystem();

        $reader = $this->subject
            ->setDelimiter('a')
            ->build('testPath', 'testStorageId');

        self::assertSame('a', $reader->getDelimiter());
    }

    public function testIdHeaderOffsetCanBeOverridden(): void
    {
        $this->prepareFilesystem();

        $reader = $this->subject
            ->setHeaderOffset(5)
            ->build('testPath', 'testStorageId');

        self::assertSame(5, $reader->getHeaderOffset());
    }

    public function testIfEnclosureCanBeOverridden(): void
    {
        $this->prepareFilesystem();

        $reader = $this->subject
            ->setEnclosure('b')
            ->build('testPath', 'testStorageId');

        self::assertSame('b', $reader->getEnclosure());
    }

    private function prepareFilesystem(): void
    {
        $this->filesystem
            ->expects(self::once())
            ->method('readStream')
            ->willReturn(fopen('php://memory', 'rb+'));

        $this->storageRegistry
            ->expects(self::once())
            ->method('getFilesystem')
            ->with('testStorageId')
            ->willReturn($this->filesystem);
    }
}
