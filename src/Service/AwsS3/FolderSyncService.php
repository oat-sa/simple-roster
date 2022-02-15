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

use Aws\S3\Exception\S3Exception;
use League\Flysystem\MountManager;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;

class FolderSyncService
{
    private const FILESYSTEM_MOUNT_LOCAL_PREFIX = 'local';
    private const FILESYSTEM_MOUNT_S3_PREFIX = 's3';

    private Filesystem $filesystemLocal;
    private Filesystem $filesystemS3;
    private LoggerInterface $logger;
    private string $awsS3Bucket;

    public function __construct(
        Filesystem $filesystemLocal,
        Filesystem $filesystemS3,
        string $awsS3Bucket,
        LoggerInterface $logger
    ) {
        $this->filesystemLocal = $filesystemLocal;
        $this->filesystemS3 = $filesystemS3;
        $this->awsS3Bucket = $awsS3Bucket;
        $this->logger = $logger;
    }

    public function copyUserFiles(): ?string
    {
        try {
            $mountManager = new MountManager([
                self::FILESYSTEM_MOUNT_LOCAL_PREFIX => $this->filesystemLocal,
                self::FILESYSTEM_MOUNT_S3_PREFIX => $this->filesystemS3,
            ]);

            $contents = $this->filesystemLocal->listContents(date('Y-m-d'), true);
            foreach ($contents as $item) {
                if ('file' === $item['type']) {
                    $sourcePath = sprintf('%s://%s', self::FILESYSTEM_MOUNT_LOCAL_PREFIX, $item['path']);
                    $destinationPath = sprintf('%s://%s', self::FILESYSTEM_MOUNT_S3_PREFIX, $item['path']);

                    if ($mountManager->has($destinationPath)) {
                        $resource = $mountManager->readStream($sourcePath);
                        if ($resource !== false) {
                            $mountManager->putStream($destinationPath, $resource);
                        }
                    } else {
                        $mountManager->copy($sourcePath, $destinationPath);
                    }
                }
            }
        } catch (S3Exception $exception) {
            $this->logger->error($exception->getMessage());

            return $exception->getMessage();
        }

        return null;
    }
}
