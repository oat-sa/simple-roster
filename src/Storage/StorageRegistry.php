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

namespace OAT\SimpleRoster\Storage;

use League\Flysystem\Filesystem;
use LogicException;
use OAT\SimpleRoster\Storage\Exception\StorageNotFoundException;

class StorageRegistry
{
    public const DEFAULT_STORAGE = 'default';

    /** @var iterable|Storage[] */
    private $storages;

    /**
     * @throws LogicException
     */
    public function __construct(iterable $storages)
    {
        foreach ($storages as $storage) {
            if (!$storage instanceof Storage) {
                throw new LogicException('Invalid storage instance type received.');
            }
        }

        $this->storages = $storages;
    }

    /**
     * @throws StorageNotFoundException
     */
    public function getFilesystem(string $storageId = self::DEFAULT_STORAGE): Filesystem
    {
        foreach ($this->storages as $storage) {
            if ($storageId === $storage->getId()) {
                return $storage->getFileSystem();
            }
        }

        throw new StorageNotFoundException(sprintf("Storage '%s' is not configured.", $storageId));
    }

    /**
     * @return string[]
     */
    public function getAllStorageIds(): array
    {
        $ids = [];
        foreach ($this->storages as $storage) {
            $ids[] = $storage->getId();
        }

        return $ids;
    }
}
