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

namespace OAT\SimpleRoster\Csv;

use Aws\S3\Exception\S3Exception;
use League\Flysystem\MountManager;
use League\Flysystem\Filesystem;

class AwsS3CsvWriter
{
    public function __construct(
        Filesystem $publicUploadsFilesystem,
        Filesystem $localUploadsFilesystem
    ) {
        $this->filesystemS3 = $publicUploadsFilesystem;
        $this->filesystemLocal = $localUploadsFilesystem;
    }

    public function writeCsv(): void
    {
        try {
            $mountManager = new MountManager([
                'local' => $this->filesystemLocal,
                's3' => $this->filesystemS3,
            ]);

            $contents = $this->filesystemLocal->listContents(date('Y-m-d'), true);
            foreach ($contents as $item) {
                if ('file' === $item['type']) {
                    $sourcePath = sprintf('local://%s', $item['path']);
                    $destinationPath = sprintf('s3://%s', $item['path']);

                    if ($mountManager->has($destinationPath)) {
                        $resource = $mountManager->readStream($sourcePath);
                        $mountManager->putStream($destinationPath, $resource);
                    } else {
                        $mountManager->copy($sourcePath, $destinationPath);
                    }
                }
            }
        } catch (S3Exception $e) {
            return;
        }

        return;
    }
}
