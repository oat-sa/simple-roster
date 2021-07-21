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

use OAT\SimpleRoster\Request\Criteria\LineItemFindCriteriaFactory;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\LineItem\LineItemService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListLineItemsAction
{
    public const LINE_ITEM_LIMIT = 100;

    private SerializerResponder $responder;
    private LineItemFindCriteriaFactory $lineItemFindCriteriaFactory;
    private LineItemService $lineItemService;

    public function __construct(
        SerializerResponder $responder,
        LineItemFindCriteriaFactory $lineItemFindCriteriaFactory,
        LineItemService $lineItemService
    ) {
        $this->responder = $responder;
        $this->lineItemService = $lineItemService;
        $this->lineItemFindCriteriaFactory = $lineItemFindCriteriaFactory;
    }

    public function __invoke(Request $request): Response
    {
        $findLineItemCriteria = $this->lineItemFindCriteriaFactory->create($request);

        $cursor = $request->get('cursor') ? (int) $request->get('cursor') : null;
        $limit = ($request->get('limit') === null || (int) $request->get('limit') > self::LINE_ITEM_LIMIT)
            ? self::LINE_ITEM_LIMIT
            : (int) $request->get('limit');

        return $this->responder->createJsonResponse(
            $this->lineItemService->listLineItems($findLineItemCriteria, $limit, $cursor)
        );
    }
}
