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

namespace OAT\SimpleRoster\Request\ParamConverter;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Request\Validator\CreateLineItemValidator;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class CreateLineItemParamConverter implements ParamConverterInterface
{
    private CreateLineItemValidator $createLineItemValidator;
    private LoggerInterface $requestLogger;

    public function __construct(
        CreateLineItemValidator $createLineItemValidator,
        LoggerInterface $requestLogger
    ) {
        $this->createLineItemValidator = $createLineItemValidator;
        $this->requestLogger = $requestLogger;
    }

    public function apply(Request $request, ParamConverter $configuration)
    {
        $this->createLineItemValidator->validate($request);

        $responseBody = json_decode($request->getContent(), true);

        $this->requestLogger->info('UpdateLineItems payload.', $responseBody);

        $request->attributes->set($configuration->getName(), $this->createLineItem($responseBody));

        return true;
    }

    private function createLineItem(array $rawLineItem): LineItem
    {
        $lineItem =
            (new LineItem())
                ->setUri($rawLineItem['uri'])
                ->setLabel($rawLineItem['label'])
                ->setSlug($rawLineItem['slug'])
                ->setIsActive($rawLineItem['isActive'])
                ->setMaxAttempts((int)$rawLineItem['maxAttempts']);

        if (isset($rawLineItem['startDateTime']) && $rawLineItem['endDateTime']) {
            $lineItem
                ->setStartAt($this->formatDate($rawLineItem['startDateTime']))
                ->setEndAt($this->formatDate($rawLineItem['endDateTime']));
        }

        return $lineItem;
    }

    private function formatDate(string $dateTime): DateTimeInterface
    {
        $dateTimeObject = Carbon::createFromFormat(Carbon::ATOM, $dateTime);

        return $dateTimeObject->setTimezone('UTC')->toDateTime();
    }

    public function supports(ParamConverter $configuration): bool
    {
        return LineItem::class === $configuration->getClass();
    }
}
