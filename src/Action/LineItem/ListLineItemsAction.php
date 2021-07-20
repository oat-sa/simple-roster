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

namespace OAT\SimpleRoster\Action\LineItem;

use Carbon\Carbon;
use OAT\SimpleRoster\Repository\Criteria\FindLineItemCriteria;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\LineItem\LineItemService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListLineItemsAction
{
    private SerializerResponder $responder;
    private LineItemService $lineItemService;

    public function __construct(SerializerResponder $responder, LineItemService $lineItemService)
    {
        $this->responder = $responder;
        $this->lineItemService = $lineItemService;
    }

    public function __invoke(Request $request): Response
    {
        $findLineItemCriteria = new FindLineItemCriteria();

        if ($request->get('id')) {
            $findLineItemCriteria->addLineItemIds((int)$request->get('id'));
        }

        if ($request->get('slug')) {
            $findLineItemCriteria->addLineItemSlugs(...$request->get('slug'));
        }

        if ($request->get('label')) {
            $findLineItemCriteria->addLineItemLabels(...$request->get('label'));
        }

        if ($request->get('uri')) {
            $findLineItemCriteria->addLineItemUris(...$request->get('uri'));
        }

        if ($request->get('startAt')) {
            $findLineItemCriteria->addLineItemStartAt(Carbon::createFromTimestamp($request->get('startAt')));
        }

        if ($request->get('endAt')) {
            $findLineItemCriteria->addLineItemEndAt(Carbon::createFromTimestamp($request->get('endAt')));
        }

        return $this->responder->createJsonResponse([
            'data' => $this->lineItemService->listLineItems($findLineItemCriteria),
            'metadata' => []
        ]);
    }
}
