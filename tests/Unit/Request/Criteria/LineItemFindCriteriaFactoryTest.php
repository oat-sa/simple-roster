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
 *  Copyright (c) 2021 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Tests\Unit\Request\Criteria;

use DateTimeImmutable;
use InvalidArgumentException;
use OAT\SimpleRoster\Request\Criteria\LineItemFindCriteriaFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LineItemFindCriteriaFactoryTest extends TestCase
{
    /**
     * @dataProvider provideInvalidTimeStamps
     */
    public function testShouldThrowInvalidForInvalidTimeStamps(string $timeStamp, string $field): void
    {
        $subject = new LineItemFindCriteriaFactory();
        $request = new Request([$field => $timeStamp]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid timestamp for %s: %s', $field, $timeStamp));

        $subject->create($request);
    }

    /**
     * @dataProvider provideValidParameters
     */
    public function testCreate(array $parameters, array $expectations): void
    {
        $subject = new LineItemFindCriteriaFactory();
        $request = new Request($parameters);

        $findLineItemCriteria = $subject->create($request);

        self::assertSame($expectations['id'], $findLineItemCriteria->getLineItemIds());
        self::assertSame($expectations['slug'], $findLineItemCriteria->getLineItemSlugs());
        self::assertSame($expectations['label'], $findLineItemCriteria->getLineItemLabels());
        self::assertSame($expectations['uri'], $findLineItemCriteria->getLineItemUris());
        self::assertSame($expectations['startAt'], $findLineItemCriteria->getLineItemStartAt()->getTimestamp());
        self::assertSame($expectations['endAt'], $findLineItemCriteria->getLineItemEndAt()->getTimestamp());
    }

    public function provideInvalidTimeStamps(): array
    {
        return [
            'startTimeStampIsZero' => [
                'timestamp' => '0',
                'field' => 'startAt',
            ],
            'startTimeStampIsNegative' => [
                'timestamp' => '-1',
                'field' => 'startAt',
            ],
            'startTimeStampIsString' => [
                'timestamp' => 'abc',
                'field' => 'startAt',
            ],
            'endTimeStampIsZero' => [
                'timestamp' => '0',
                'field' => 'endAt',
            ],
            'endTimeStampIsNegative' => [
                'timestamp' => '-1',
                'field' => 'endAt',
            ],
            'endTimeStampIsString' => [
                'timestamp' => 'abc',
                'field' => 'endAt',
            ],
        ];
    }

    public function provideValidParameters(): array
    {
        $timeStamp = (new DateTimeImmutable())->getTimestamp();

        return [
            'parametersInSingleFormat' => [
              'parameters' => [
                  'id' => 1,
                  'slug' => 'slug-1',
                  'label' => 'label-1',
                  'uri' => 'uri-1',
                  'startAt' => $timeStamp,
                  'endAt' => $timeStamp,
              ],
              'expectations' => [
                  'id' => [1],
                  'slug' => ['slug-1'],
                  'label' => ['label-1'],
                  'uri' => ['uri-1'],
                  'startAt' => $timeStamp,
                  'endAt' => $timeStamp,
              ]
            ],
            'parametersInArrayFormat' => [
                'parameters' => [
                    'id' => 1,
                    'slug' => ['slug-1','slug-1','slug-1'],
                    'label' => ['label-1','label-2','label-3'],
                    'uri' => ['uri-1','uri-2','uri-3'],
                    'startAt' => $timeStamp,
                    'endAt' => $timeStamp,
                ],
                'expectations' => [
                    'id' => [1],
                    'slug' => ['slug-1','slug-1','slug-1'],
                    'label' => ['label-1','label-2','label-3'],
                    'uri' => ['uri-1','uri-2','uri-3'],
                    'startAt' => $timeStamp,
                    'endAt' => $timeStamp,
                ]
            ]
        ];
    }
}
