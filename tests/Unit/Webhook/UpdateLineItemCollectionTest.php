<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Webhook;

use DateTimeImmutable;
use Exception;
use Iterator;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;
use PHPUnit\Framework\TestCase;

class UpdateLineItemCollectionTest extends TestCase
{
    public function testItThrowsExceptionWhenEmptyCollection(): void
    {
        $this->expectException(Exception::class);

        $collection = new UpdateLineItemCollection();

        $collection->findLastByTriggeredTimeOrFail();
    }

    public function testItFindsLastByTriggeredTime(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                (new DateTimeImmutable())->setTimestamp(1565602371)
            ),
            new UpdateLineItemDto(
                '22',
                'test',
                'http://i.o',
                (new DateTimeImmutable())->setTimestamp(1565602380)
            )
        );

        $dto = $collection->findLastByTriggeredTimeOrFail();
        $this->assertEquals('22', $dto->getId());
    }

    public function testAccessors(): void
    {
        $collection = new UpdateLineItemCollection();
        $this->assertInstanceOf(Iterator::class, $collection->getIterator());
    }

    public function testItMaps(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto('11', 'test', 'http://i.o', new DateTimeImmutable()),
            new UpdateLineItemDto('22', 'test', 'http://i.o', new DateTimeImmutable())
        );

        $ids = $collection->map(
            function (UpdateLineItemDto $updateLineItemDto): string {
                return $updateLineItemDto->getId();
            }
        );

        $this->assertEquals(2, count($ids));

        $this->assertEquals('11', $ids[0]);
        $this->assertEquals('22', $ids[1]);
    }

    public function testItFilters(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto('11', 'test', 'http://i.o', new DateTimeImmutable()),
            new UpdateLineItemDto('22', 'test', 'http://i.o', new DateTimeImmutable())
        );

        $this->assertEquals(2, $collection->count());

        $filtered = $collection->filter(
            function (UpdateLineItemDto $updateLineItemDto): bool {
                return '22' === $updateLineItemDto->getId();
            }
        );
        $this->assertEquals(1, $filtered->count());
    }

    public function testItSetStatus(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto('11', 'test', 'http://i.o', new DateTimeImmutable()),
            new UpdateLineItemDto('22', 'test', 'http://i.o', new DateTimeImmutable())
        );

        foreach ($collection as $dto) {
            $this->assertEquals('ignored', $dto->getStatus());
        }

        $collectionAccepted = $collection->setStatus('accepted');

        foreach ($collectionAccepted as $dto) {
            $this->assertEquals('accepted', $dto->getStatus());
        }
    }

    public function testItCounts(): void
    {
        $emptyCollection = new UpdateLineItemCollection();
        $this->assertEquals(0, $emptyCollection->count());

        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto('11', 'test', 'http://i.o', new DateTimeImmutable())
        );

        $this->assertEquals(1, $collection->count());
    }
}
