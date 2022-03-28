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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Events;

use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The `line-item.updated` event is dispatched on webhook that updates/creates a line-item.
 * @see \OAT\SimpleRoster\Action\WebHook\UpdateLineItemsWebhookAction
 */
class LineItemUpdated extends Event
{
    public const NAME = 'line-item.updated';

    /** @var string[] $updateLineItemCollection */
    protected array $updateLineItemCollection;

    public function __construct(array $updateLineItemCollection)
    {
        $this->updateLineItemCollection = $updateLineItemCollection;
    }

    public function getLineItemSlugs(): array
    {
        return $this->updateLineItemCollection;
    }
}
