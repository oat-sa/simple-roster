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

namespace OAT\SimpleRoster\Request\Criteria;

use Carbon\Carbon;
use InvalidArgumentException;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use Symfony\Component\HttpFoundation\Request;

/**
 * @throws InvalidArgumentException
 */
class LineItemFindCriteriaFactory
{
    public function create(Request $request): FindLineItemCriteria
    {
        $findLineItemCriteria = new FindLineItemCriteria();

        if ($request->get('id') !== null) {
            $findLineItemCriteria->addLineItemIds((int)$request->get('id'));
        }

        if ($request->get('slug') !== null) {
            $findLineItemCriteria->addLineItemSlugs(...(array) $request->get('slug'));
        }

        if ($request->get('label') !== null) {
            $findLineItemCriteria->addLineItemLabels(...(array) $request->get('label'));
        }

        if ($request->get('uri') !== null) {
            $findLineItemCriteria->addLineItemUris(...(array) $request->get('uri'));
        }

        $this->applyDateFilter($request, $findLineItemCriteria, 'startAt', 'addLineItemStartAt');
        $this->applyDateFilter($request, $findLineItemCriteria, 'endAt', 'addLineItemEndAt');

        return $findLineItemCriteria;
    }

    private function applyDateFilter(
        Request $request,
        FindLineItemCriteria $findLineItemCriteria,
        string $field,
        string $method
    ): void {
        if ($request->get($field) !== null) {
            $timestamp = (int) $request->get($field);

            if ($timestamp <= 0) {
                throw new InvalidArgumentException(
                    sprintf('Invalid timestamp for %s: %s', $field, $request->get($field))
                );
            }

            $findLineItemCriteria->{$method}(Carbon::createFromTimestamp($timestamp));
        }
    }
}
