<?php

declare(strict_types=1);

/**
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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

namespace App\Ingester\Source;

use Aws\S3\S3Client;
use League\Csv\Exception;
use League\Csv\Reader;
use Traversable;

class S3CsvIngesterSource extends AbstractIngesterSource
{
    /** @var S3Client */
    private $client;

    /** @var string  */
    private $bucket;

    public function __construct(S3Client $client, string $bucket)
    {
        $this->client = $client;
        $this->bucket = $bucket;
    }

    public function getRegistryItemName(): string
    {
        return 's3';
    }

    /**
     * @throws Exception
     */
    public function getContent(): Traversable
    {
        $result = $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key'    => $this->path
        ]);

        $reader = Reader::createFromString((string)($result['Body'] ?? ''));

        $reader
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset(0);

        if ($this->charset !== self::DEFAULT_CSV_CHARSET) {
            $reader->addStreamFilter(sprintf('convert.iconv.%s/UTF-8', $this->charset));
        }

        return $reader;
    }
}
