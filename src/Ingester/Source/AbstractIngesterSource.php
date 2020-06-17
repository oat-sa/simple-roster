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

abstract class AbstractIngesterSource implements IngesterSourceInterface
{
    /** @var string */
    protected $path = '';

    /** @var string */
    protected $delimiter = self::DEFAULT_CSV_DELIMITER;

    /** @var string */
    protected $charset = self::DEFAULT_CSV_CHARSET;

    public function setPath(string $path): IngesterSourceInterface
    {
        $this->path = $path;

        return $this;
    }

    public function setDelimiter(string $delimiter): IngesterSourceInterface
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function setCharset(string $charset): IngesterSourceInterface
    {
        $this->charset = $charset;

        return $this;
    }
}
