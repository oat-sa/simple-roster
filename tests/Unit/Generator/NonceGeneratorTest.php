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

namespace OAT\SimpleRoster\Tests\Unit\Generator;

use Carbon\Carbon;
use OAT\SimpleRoster\Generator\NonceGenerator;
use PHPUnit\Framework\TestCase;

class NonceGeneratorTest extends TestCase
{
    public function testItGeneratesUniqueNonce(): void
    {
        $subject = new NonceGenerator();

        Carbon::setTestNow(Carbon::create(2019));
        $nonce1 = $subject->generate();

        Carbon::setTestNow(Carbon::create(2019, 1, 2));
        $nonce2 = $subject->generate();

        self::assertNotEquals($nonce1, $nonce2);
    }
}
