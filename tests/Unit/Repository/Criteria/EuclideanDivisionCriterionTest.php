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

namespace App\Tests\Unit\Repository\Criteria;

use App\Repository\Criteria\EuclideanDivisionCriterion;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EuclideanDivisionCriterionTest extends TestCase
{
    /**
     * @dataProvider provideInvalidModuloRemainderPairs
     */
    public function testItValidatesModuloAndRemainder(int $modulo, int $remainder, string $exceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        new EuclideanDivisionCriterion($modulo, $remainder);
    }

    public function provideInvalidModuloRemainderPairs(): array
    {
        return [
            'tooLowModulo' => [
                'modulo' => 1,
                'remainder' => 2,
                'exceptionMessage' => 'Modulo must be greater than 1',
            ],
            'tooLowRemainder' => [
                'modulo' => 2,
                'remainder' => -1,
                'exceptionMessage' => 'Remainder must be greater or equal to 0',
            ],
            'tooHighRemainder' => [
                'modulo' => 3,
                'remainder' => 3,
                'exceptionMessage' => 'Remainder must be less than 3',
            ]
        ];
    }
}
