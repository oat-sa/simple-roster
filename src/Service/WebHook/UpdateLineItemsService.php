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

namespace OAT\SimpleRoster\Service\WebHook;

use OAT\SimpleRoster\Repository\LineItemRepository;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;

class UpdateLineItemsService
{
    /** @var LineItemRepository */
    private $lineItemRepository;

    public function __construct(LineItemRepository $lineItemRepository)
    {
        $this->lineItemRepository = $lineItemRepository;
    }

    public function handleUpdates(UpdateLineItemCollection $collection): UpdateLineItemCollection
    {
        $knownUpdates = $collection
            ->filter(
                function (UpdateLineItemDto $dto): bool {
                    return $dto->getName() === UpdateLineItemDto::NAME;
                }
            )
            ->setStatus(UpdateLineItemDto::STATUS_ERROR);

        $slugs = $knownUpdates
            ->map(
                function (UpdateLineItemDto $dto): string {
                    return $dto->getSlug();
                }
            );

        if (count($slugs) < 1) {
            return $collection;
        }

        $lineItems = $this->lineItemRepository->findBy(
            [
                'slug' => (array)$slugs
            ]
        );

        foreach ($lineItems as $lineItem) {
            $dto = $knownUpdates
                ->filter(
                    function (UpdateLineItemDto $dto) use ($lineItem): bool {
                        return $dto->getSlug() === $lineItem->getSlug();
                    }
                )
                ->setStatus(UpdateLineItemDto::STATUS_IGNORED)
                ->findLastByTriggeredTime();

            //TODO add on the queue
            $lineItem->setUri($dto->getLineItemUri());
            //TODO add on the queue

            $dto->setStatus(UpdateLineItemDto::STATUS_ACCEPTED);
        }

        return $collection;
    }
}
