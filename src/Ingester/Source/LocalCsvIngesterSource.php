<?php

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

declare(strict_types=1);

namespace App\Ingester\Source;

use League\Csv\Exception;
use League\Csv\Reader;
use Traversable;

class LocalCsvIngesterSource extends AbstractIngesterSource
{
    /** @var Reader|null */
    private $reader;

    public function getRegistryItemName(): string
    {
        return 'local';
    }

    /**
     * @throws Exception
     */
    public function getContent(): Traversable
    {
        return $this->getReader();
    }

    /**
     * @throws Exception
     */
    public function count(): int
    {
        return count($this->getReader());
    }

    /**
     * @throws Exception
     */
    private function getReader(): Reader
    {
        if ($this->reader) {
            return $this->reader;
        }

        $this->reader = Reader::createFromPath($this->path);

        $this->reader
            ->setDelimiter($this->delimiter)
            ->setHeaderOffset(0);

        if ($this->charset !== self::DEFAULT_CSV_CHARSET) {
            $this->reader->addStreamFilter(sprintf('convert.iconv.%s/UTF-8', $this->charset));
        }

        return $this->reader;
    }
}
