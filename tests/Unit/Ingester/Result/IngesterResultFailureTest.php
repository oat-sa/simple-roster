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

namespace OAT\SimpleRoster\Tests\Unit\Ingester\Result;

use OAT\SimpleRoster\Ingester\Result\IngesterResultFailure;
use PHPUnit\Framework\TestCase;

class IngesterResultFailureTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new IngesterResultFailure(10, ['data'], 'reason');

        self::assertSame(10, $subject->getLineNumber());
        self::assertSame(['data'], $subject->getData());
        self::assertSame('reason', $subject->getReason());
    }
}
