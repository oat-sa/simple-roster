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

namespace OAT\SimpleRoster\Csv;

use League\Csv\Exception;
use League\Csv\Reader;
use League\Flysystem\FileNotFoundException;
use OAT\SimpleRoster\Csv\Exception\StreamResourceNotFoundException;
use OAT\SimpleRoster\Storage\Exception\StorageNotFoundException;
use OAT\SimpleRoster\Storage\StorageRegistry;

class CsvReaderBuilder
{
    public const DEFAULT_CSV_DELIMITER = ',';
    public const DEFAULT_CSV_ENCLOSURE = '"';

    /** @var StorageRegistry */
    private StorageRegistry $storageRegistry;

    /** @var int */
    private int $headerOffset = 0;

    /** @var string */
    private string $delimiter = self::DEFAULT_CSV_DELIMITER;

    /** @var string */
    private string $enclosure = self::DEFAULT_CSV_ENCLOSURE;

    public function __construct(StorageRegistry $storageRegistry)
    {
        $this->storageRegistry = $storageRegistry;
    }

    /**
     * @throws FileNotFoundException
     * @throws StorageNotFoundException
     * @throws Exception
     */
    public function build(string $relativePath, string $storageId = StorageRegistry::DEFAULT_STORAGE): Reader
    {
        $fileSystem = $this->storageRegistry->getFilesystem($storageId);
        $stream = $fileSystem->readStream($relativePath);

        if (!is_resource($stream)) {
            throw new StreamResourceNotFoundException(sprintf("Resource not found: '%s'", $relativePath));
        }

        $reader = Reader::createFromStream($stream);

        return $reader
            ->setHeaderOffset($this->headerOffset)
            ->setDelimiter($this->delimiter)
            ->setEnclosure($this->enclosure);
    }

    public function setHeaderOffset(int $headerOffset): self
    {
        $this->headerOffset = $headerOffset;

        return $this;
    }

    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function setEnclosure(string $enclosure): self
    {
        $this->enclosure = $enclosure;

        return $this;
    }
}
