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

namespace OAT\SimpleRoster\Tests\Unit\Http\Exception;

use Exception;
use OAT\SimpleRoster\Http\Exception\RequestEntityTooLargeHttpException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RequestEntityTooLargeHttpExceptionTest extends TestCase
{
    public function testItIsException(): void
    {
        self::assertInstanceOf(RuntimeException::class, new RequestEntityTooLargeHttpException('message'));
    }

    public function testDefaultValues(): void
    {
        $previousException = new Exception();
        $subject = new RequestEntityTooLargeHttpException('Custom error message', $previousException);

        self::assertSame(413, $subject->getStatusCode());
        self::assertSame(0, $subject->getCode());
        self::assertSame('Custom error message', $subject->getMessage());
        self::assertSame($previousException, $subject->getPrevious());
    }
}
