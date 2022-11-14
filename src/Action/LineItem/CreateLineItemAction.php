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

use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\Service\LineItem\LineItemService;
use Symfony\Component\HttpFoundation\JsonResponse;

class CreateLineItemAction
{
    private SerializerResponder $responder;
    private LineItemService $lineItemService;

    public function __construct(
        LineItemService $lineItemService,
        SerializerResponder $responder
    ) {
        $this->lineItemService = $lineItemService;
        $this->responder = $responder;
    }

    public function __invoke(LineItem $lineItem): JsonResponse
    {
        return $this->responder->createJsonResponse(
            $this->lineItemService->createOrUpdateLineItem($lineItem),
            JsonResponse::HTTP_CREATED
        );
    }
}
