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

namespace OAT\SimpleRoster\Action\WebHook;

use OAT\SimpleRoster\Events\LineItemUpdated;
use OAT\SimpleRoster\Responder\SerializerResponder;
use OAT\SimpleRoster\WebHook\Service\UpdateLineItemsService;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as EventDispatcher;
use Symfony\Component\HttpFoundation\Response;

class UpdateLineItemsWebhookAction
{
    private SerializerResponder $responder;
    private UpdateLineItemsService $service;
    private EventDispatcher $eventDispatcher;

    public function __construct(
        SerializerResponder $responder,
        UpdateLineItemsService $service,
        EventDispatcher $eventDispatcher
    ) {
        $this->responder = $responder;
        $this->service = $service;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __invoke(UpdateLineItemCollection $collection): Response
    {
        $updatedCollection = $this->service->handleUpdates($collection);

        $this->eventDispatcher->dispatch(new LineItemUpdated($updatedCollection), LineItemUpdated::NAME);

        return $this->responder->createJsonResponse($updatedCollection);
    }
}
