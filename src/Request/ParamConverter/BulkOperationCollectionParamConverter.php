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
 *  Copyright (c) 2019 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Request\ParamConverter;

use JsonException;
use OAT\SimpleRoster\Bulk\Operation\BulkOperation;
use OAT\SimpleRoster\Bulk\Operation\BulkOperationCollection;
use OAT\SimpleRoster\Http\Exception\RequestEntityTooLargeHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BulkOperationCollectionParamConverter implements ParamConverterInterface
{
    public const BULK_OPERATIONS_LIMIT = 1000;

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $class = $configuration->getClass();
        $param = $configuration->getName();

        /** @var BulkOperationCollection $collection */
        $collection = new $class();

        foreach ($this->extractOperationsFromRequest($request) as $operation) {
            $bulkOperation = new BulkOperation(
                $operation['identifier'],
                $this->getBulkOperationTypeFromRequest($request),
                $operation['attributes'] ?? []
            );

            $collection->add($bulkOperation);
        }

        $request->attributes->set($param, $collection);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return BulkOperationCollection::class === $configuration->getClass();
    }

    /**
     * @throws BadRequestHttpException
     * @throws RequestEntityTooLargeHttpException
     */
    private function extractOperationsFromRequest(Request $request): array
    {
        try {
            $operations = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new BadRequestHttpException(
                sprintf(
                    'Invalid JSON request body received. Error: %s',
                    $jsonException->getMessage()
                ),
                $jsonException
            );
        }

        if (empty($operations)) {
            throw new BadRequestHttpException('Empty request body received.');
        }

        if (count($operations) > static::BULK_OPERATIONS_LIMIT) {
            throw new RequestEntityTooLargeHttpException(
                sprintf(
                    "Bulk operation limit has been exceeded, maximum of '%s' allowed per request.",
                    static::BULK_OPERATIONS_LIMIT
                )
            );
        }

        return $operations;
    }

    private function getBulkOperationTypeFromRequest(Request $request): string
    {
        return $request->getMethod() === Request::METHOD_PATCH
            ? BulkOperation::TYPE_UPDATE
            : BulkOperation::TYPE_CREATE;
    }
}
