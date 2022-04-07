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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

namespace OAT\SimpleRoster\Tests\Unit\Lti\Service\UserGenerator;

use OAT\SimpleRoster\Lti\Service\UserGenerator\ParametersBag;
use PHPUnit\Framework\TestCase;

class ParametersBagTest extends TestCase
{
    public function testConstruction(): void
    {
        $obj = new ParametersBag(
            $prefix = 'test_prefix',
            $prefixes = ['test1', 'test2'],
            $batchSize = 777,
        );

        self::assertEquals($prefix, $obj->getGroupPrefix());
        self::assertEquals($prefixes, $obj->getPrefixes());
        self::assertEquals($batchSize, $obj->getBatchSize());
    }
}
