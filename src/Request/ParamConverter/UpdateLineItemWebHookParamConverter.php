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

namespace OAT\SimpleRoster\Request\ParamConverter;

use DateTimeImmutable;
use OAT\SimpleRoster\Request\Validator\UpdateLineItemValidator;
use OAT\SimpleRoster\WebHook\UpdateLineItemCollection;
use OAT\SimpleRoster\WebHook\UpdateLineItemDto;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class UpdateLineItemWebHookParamConverter implements ParamConverterInterface
{
    private UpdateLineItemValidator $updateLineItemValidator;
    private LoggerInterface $logger;

    public function __construct(UpdateLineItemValidator $updateLineItemValidator, LoggerInterface $requestLogger)
    {
        $this->updateLineItemValidator = $updateLineItemValidator;
        $this->logger = $requestLogger;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $this->updateLineItemValidator->validate($request);

        $responseBody = json_decode($request->getContent(), true);
        $eventsRaw = $responseBody['events'];
        $events = [];

        $this->logger->info('UpdateLineItems payload.', $responseBody);

        foreach ($eventsRaw as $event) {
            $events[] = new UpdateLineItemDto(
                (string)$event['eventId'],
                (string)$event['eventName'],
                (string)$event['eventData']['remoteDeliveryId'],
                (new DateTimeImmutable())->setTimestamp($event['triggeredTimestamp']),
                $event['eventData']['alias'] ?? null
            );
        }

        $request->attributes->set($configuration->getName(), new UpdateLineItemCollection(...$events));

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return UpdateLineItemCollection::class === $configuration->getClass();
    }
}
