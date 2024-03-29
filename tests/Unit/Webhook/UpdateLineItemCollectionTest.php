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
    public function testItThrowsExceptionWhenEmptyCollectionIsFilteredByTriggeredTime(): void
    {
        $this->expectException(Exception::class);

        $collection = new UpdateLineItemCollection();

        $collection->findLastByTriggeredTimeOrFail();
    }

    public function testItFindsLastByTriggeredTime(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '22',
                'test',
                'http://i.o',
                (new DateTimeImmutable())->setTimestamp(1565602380),
                'qti-interactions-delivery1',
                'qti-interactions-delivery1-label',
                1665561600,
                1666094400,
                2
            ),
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                (new DateTimeImmutable())->setTimestamp(1565602371),
                'qti-interactions-delivery',
                'qti-interactions-delivery-label',
                1665561600,
                1666094400,
                2
            )
        );

        $dto = $collection->findLastByTriggeredTimeOrFail();
        self::assertSame('22', $dto->getId());
    }

    public function testItFindsLastByTriggeredTimeDuplicatedTime(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                (new DateTimeImmutable())->setTimestamp(1565602380),
                'qti-interactions-delivery',
                'qti-interactions-delivery-label',
                1665561600,
                1666094400,
                2
            ),
            new UpdateLineItemDto(
                '22',
                'test',
                'http://i.o',
                (new DateTimeImmutable())->setTimestamp(1565602380),
                'qti-interactions-delivery1',
                'qti-interactions-delivery1-label',
                1665561600,
                1666094400,
                2
            )
        );

        $dto = $collection->findLastByTriggeredTimeOrFail();
        self::assertSame('22', $dto->getId());
    }

    public function testAccessors(): void
    {
        $collection = new UpdateLineItemCollection();
        self::assertInstanceOf(Iterator::class, $collection->getIterator());
    }

    public function testItMaps(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery',
                'qti-interactions-delivery-label',
                1665561600,
                1666094400,
                2
            ),
            new UpdateLineItemDto(
                '22',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery1',
                'qti-interactions-delivery1-label',
                1665561600,
                1666094400,
                2
            )
        );

        $ids = $collection->map(
            function (UpdateLineItemDto $updateLineItemDto): string {
                return $updateLineItemDto->getId();
            }
        );

        self::assertCount(2, $ids);

        self::assertSame('11', $ids[0]);
        self::assertSame('22', $ids[1]);
    }

    public function testItFilters(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery',
                'qti-interactions-delivery-label',
                1665561600,
                1666094400,
                2
            ),
            new UpdateLineItemDto(
                '22',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery1',
                'qti-interactions-delivery1-label',
                1665561600,
                1666094400,
                2
            )
        );

        self::assertCount(2, $collection);

        $filtered = $collection->filter(
            function (UpdateLineItemDto $updateLineItemDto): bool {
                return '22' === $updateLineItemDto->getId();
            }
        );
        self::assertCount(1, $filtered);
    }

    public function testItSetStatus(): void
    {
        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery',
                'qti-interactions-delivery-label',
                1665561600,
                1666094400,
                2
            ),
            new UpdateLineItemDto(
                '22',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery1',
                'qti-interactions-delivery1-label',
                1665561600,
                1666094400,
                2
            )
        );

        foreach ($collection as $dto) {
            self::assertSame('ignored', $dto->getStatus());
        }

        $collectionAccepted = $collection->setStatus('accepted');

        foreach ($collectionAccepted as $dto) {
            self::assertSame('accepted', $dto->getStatus());
        }
    }

    public function testItCounts(): void
    {
        $emptyCollection = new UpdateLineItemCollection();
        self::assertCount(0, $emptyCollection);

        $collection = new UpdateLineItemCollection(
            new UpdateLineItemDto(
                '11',
                'test',
                'http://i.o',
                new DateTimeImmutable(),
                'qti-interactions-delivery',
                'qti-interactions-delivery-label',
                1665561600,
                1666094400,
                2
            )
        );

        self::assertCount(1, $collection);
    }
}
