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

namespace OAT\SimpleRoster\Service\AwsS3;

use League\Flysystem\{
    FileExistsException,
    FileNotFoundException,
    MountManager,
    Filesystem
};

class FolderSyncService
{
    private const FILESYSTEM_MOUNT_LOCAL_PREFIX = 'local';
    private const FILESYSTEM_MOUNT_S3_PREFIX = 's3';

    private Filesystem $filesystemLocal;
    private Filesystem $filesystemS3;

    public function __construct(
        Filesystem $filesystemLocal,
        Filesystem $filesystemS3
    ) {
        $this->filesystemLocal = $filesystemLocal;
        $this->filesystemS3 = $filesystemS3;
    }

    /**
     * @throws FileNotFoundException
     * @throws FileExistsException
     */
    public function copyUserFiles(): void
    {
        $mountManager = new MountManager([
            self::FILESYSTEM_MOUNT_LOCAL_PREFIX => $this->filesystemLocal,
            self::FILESYSTEM_MOUNT_S3_PREFIX => $this->filesystemS3,
        ]);

        $contents = $this->filesystemLocal->listContents(date('Y-m-d'), true);
        foreach ($contents as $item) {
            if ('file' !== $item['type']) {
                continue;
            }

            $from = sprintf('%s://%s', self::FILESYSTEM_MOUNT_LOCAL_PREFIX, $item['path']);
            $to = sprintf('%s://%s', self::FILESYSTEM_MOUNT_S3_PREFIX, $item['path']);

            if ($mountManager->has($to)) {
                $resource = $mountManager->readStream($from);
                if ($resource !== false) {
                    $mountManager->putStream($to, $resource);
                }
            } else {
                $mountManager->copy($from, $to);
            }
        }
    }
}
