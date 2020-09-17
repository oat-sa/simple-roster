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

namespace App\Tests\Unit\Ingester\Result;

use App\Ingester\Result\IngesterResult;
use App\Ingester\Result\IngesterResultFailure;
use PHPUnit\Framework\TestCase;

class IngesterResultTest extends TestCase
{
    public function testGettersPostConstruction(): void
    {
        $subject = new IngesterResult('ingester', 'source');

        self::assertSame('ingester', $subject->getIngesterType());
        self::assertSame('source', $subject->getSourceType());
        self::assertSame(0, $subject->getSuccessCount());
        self::assertEmpty($subject->getFailures());
        self::assertFalse($subject->hasFailures());
        self::assertTrue($subject->isDryRun());
    }

    public function testItCanAddSuccesses(): void
    {
        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addSuccess()
            ->addSuccess();

        self::assertSame(2, $subject->getSuccessCount());
        self::assertFalse($subject->hasFailures());
    }

    public function testItCanAddAndRetrieveFailures(): void
    {
        $failure1 = new IngesterResultFailure(1, ['data'], 'reason1');
        $failure2 = new IngesterResultFailure(2, ['data2'], 'reason2');

        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addFailure($failure1)
            ->addFailure($failure2);

        self::assertSame(0, $subject->getSuccessCount());
        self::assertTrue($subject->hasFailures());
        self::assertSame(
            [
                1 => $failure1,
                2 => $failure2
            ],
            $subject->getFailures()
        );
    }

    public function testPostDryRunStringRepresentation(): void
    {
        $subject = new IngesterResult('ingester', 'source');

        $subject
            ->addSuccess()
            ->addSuccess()
            ->addFailure($this->createMock(IngesterResultFailure::class));

        self::assertSame(
            "[DRY_RUN] Ingestion (type='ingester', source='source'): 2 successes, 1 failures.",
            $subject->__toString()
        );
    }

    public function testPostRunStringRepresentation(): void
    {
        $subject = new IngesterResult('ingester', 'source', false);

        $subject
            ->addSuccess()
            ->addSuccess()
            ->addFailure($this->createMock(IngesterResultFailure::class));

        self::assertSame(
            "Ingestion (type='ingester', source='source'): 2 successes, 1 failures.",
            $subject->__toString()
        );
    }
}
