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

use DateTimeImmutable;
use DateTimeInterface;
use OAT\SimpleRoster\Entity\LineItem;
use OAT\SimpleRoster\Request\Validator\LineItem\CreateLineItemValidator;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class CreateLineItemParamConverter implements ParamConverterInterface
{
    private CreateLineItemValidator $createLineItemValidator;
    private LoggerInterface $requestLogger;

    private const DEFAULT_LINE_ITEM_MAX_ATTEMPTS_COUNT = 0;
    private const DEFAULT_LINE_ITEM_ACTIVE_STATUS = true;

    public function __construct(
        CreateLineItemValidator $createLineItemValidator,
        LoggerInterface $requestLogger
    ) {
        $this->createLineItemValidator = $createLineItemValidator;
        $this->requestLogger = $requestLogger;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $this->createLineItemValidator->validate($request);

        try {
            $responseBody = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $this->requestLogger->info('UpdateLineItems payload.', $responseBody);

            $request->attributes->set($configuration->getName(), $this->createLineItem($responseBody));

            return true;
        } catch (Throwable $jsonException) {
            throw new BadRequestHttpException(
                sprintf(
                    'Invalid JSON request body received. Error: %s.',
                    $jsonException->getMessage()
                ),
                $jsonException
            );
        }
    }

    private function createLineItem(array $rawLineItem): LineItem
    {
        $lineItem =
            (new LineItem())
                ->setUri($rawLineItem['uri'])
                ->setLabel($rawLineItem['slug'])
                ->setSlug($rawLineItem['slug'])
                ->setIsActive(self::DEFAULT_LINE_ITEM_ACTIVE_STATUS)
                ->setMaxAttempts(self::DEFAULT_LINE_ITEM_MAX_ATTEMPTS_COUNT);

        if (isset($rawLineItem['startDateTime']) && $rawLineItem['endDateTime']) {
            $lineItem
                ->setStartAt($this->formatDate($rawLineItem['startDateTime']))
                ->setEndAt($this->formatDate($rawLineItem['endDateTime']));
        }

        return $lineItem;
    }

    private function formatDate(string $dateTime): DateTimeInterface
    {
        return new DateTimeImmutable($dateTime);
    }

    public function supports(ParamConverter $configuration): bool
    {
        return LineItem::class === $configuration->getClass();
    }
}
