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

use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;

class CsvWriter
{
    public const DEFAULT_CSV_CREATE_MODE = 'w';

    private Filesystem $filesystem;

    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }
    public function writeCsv(string $path, array $head, array $data): void
    {
        if (!$this->filesystem->exists($path)) {
            $csv = Writer::createFromPath($path, self::DEFAULT_CSV_CREATE_MODE);
            $csv->insertOne($head);
            $csv->insertAll($data);

            return;
        }

        $csv = Writer::createFromPath($path, 'a+');
        $csv->insertAll($data);
    }
}
