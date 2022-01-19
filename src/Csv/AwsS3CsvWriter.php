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


use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class AwsS3CsvWriter
{
    private string $awsS3AccessKey;
    private string $awsS3AccessSecretKey;
    private string $awsS3Version;
    private string $awsS3Region;
    private string $awsS3BucketName;
    private string $awsS3FilePath;

    public function __construct(
        string $awsS3AccessKey,
        string $awsS3AccessSecretKey,
        string $awsS3Version,
        string $awsS3Region,
        string $awsS3BucketName,
        string $awsS3FilePath
    ) {
        $this->awsS3AccessKey = $awsS3AccessKey;
        $this->awsS3AccessSecretKey = $awsS3AccessSecretKey;
        $this->awsS3Version = $awsS3Version;
        $this->awsS3Region = $awsS3Region;
        $this->awsS3BucketName = $awsS3BucketName;
        $this->awsS3FilePath = $awsS3FilePath;
    }

    public function writeCsv(string $sourcePath): void
    {
        try {
            $s3Client = new S3Client([
                'version'     => $this->awsS3Version,
                'region'      => $this->awsS3Region,
                'credentials' =>  [
                    'key'    => $this->awsS3AccessKey,
                    'secret' => $this->awsS3AccessSecretKey,
                ]
            ]);
            
            $destinationPath = sprintf('%s/%s', $this->awsS3FilePath, date('Y-m-d'));
            $s3Client->uploadDirectory(
                $sourcePath,
                $this->awsS3BucketName,
                $destinationPath,
                ['params' => array('ACL' => 'public-read')],
            );
        } catch (S3Exception $e) {
            throw $e;
        }
    }
}
