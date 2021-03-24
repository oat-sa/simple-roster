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

namespace OAT\SimpleRoster\Tests\Unit\Entity;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use OAT\SimpleRoster\Entity\LineItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\UuidV6;

class LineItemTest extends TestCase
{
    public function testItThrowsExceptionIfInvalidStatusReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid line item status received: 'invalidStatus'");

        new LineItem(new UuidV6('00000001-0000-6000-0000-000000000000'), 'label', 'uri', 'slug', 'invalidStatus');
    }

    public function testItThrowsExceptionIfInvalidMaxAttemptsReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'maxAttempts' must be greater or equal to zero.");

        new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED,
            -1
        );
    }

    public function testItThrowsExceptionIfInvalidAvailabilityDatesReceived(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid availability dates received. 'endAt' must be greater than 'startAt'.");

        $startAt = new DateTime('2021-03-03');
        $endAt = new DateTime('2021-03-02');

        new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED,
            0,
            $startAt,
            $endAt
        );
    }

    /**
     * @dataProvider provideAvailabilityDates
     */
    public function testItIsAvailableForDate(
        array $testCases,
        DateTimeInterface $startAt = null,
        DateTimeInterface $endAt = null
    ): void {
        $subject = new LineItem(
            new UuidV6('00000001-0000-6000-0000-000000000000'),
            'label',
            'uri',
            'slug',
            LineItem::STATUS_ENABLED
        );

        $subject->setAvailabilityDates($startAt, $endAt);

        foreach ($testCases as $testDate => $expectedAvailability) {
            self::assertSame($expectedAvailability, $subject->isAvailableForDate(new DateTime($testDate)));
        }
    }

    public function provideAvailabilityDates(): array
    {
        return [
            'noAvailabilityDatesAreSpecified' => [
                'testCases' => [
                    '2021-03-03T00:00:00+0000' => true,
                ],
                'startAt' => null,
                'endAt' => null,
            ],
            'onlyStartAtIsSpecified' => [
                'testCases' => [
                    '2021-03-02T00:00:00+0000' => false,
                    '2021-03-03T12:00:00+0000' => true,
                ],
                'startAt' => new DateTime('2021-03-03T00:00:00'),
                'endAt' => null,
            ],
            'onlyEndAtIsSpecified' => [
                'testCases' => [
                    '2021-03-02T00:00:00+0000' => true,
                    '2021-03-04T00:00:00+0000' => false,
                ],
                'startAt' => null,
                'endAt' => new DateTime('2021-03-03T00:00:00'),
            ],
            'availabilityDatesAreSpecified' => [
                'testCases' => [
                    '2021-03-02T00:00:00+0000' => false,
                    '2021-03-05T00:00:00+0000' => true,
                    '2021-03-11T00:00:00+0000' => false,
                ],
                'startAt' => new DateTime('2021-03-03T00:00:00'),
                'endAt' => new DateTime('2021-03-10T00:00:00'),
            ],
        ];
    }
}
