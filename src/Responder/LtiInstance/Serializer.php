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
 *  Copyright (c) 2022 (original work) Open Assessment Technologies S.A.
 */

declare(strict_types=1);

namespace OAT\SimpleRoster\Responder\LtiInstance;

use OAT\SimpleRoster\Entity\LtiInstance;
use OAT\SimpleRoster\Responder\SerializerResponder;
use Symfony\Component\HttpFoundation\JsonResponse;

class Serializer
{
    private SerializerResponder $responder;

    public function __construct(SerializerResponder $responder)
    {
        $this->responder = $responder;
    }

    public function json($data, int $code): JsonResponse
    {
        return $this->responder->createJsonResponse($data, $code);
    }

    public function error(string $message, int $code): JsonResponse
    {
        return $this->responder->createJsonResponse(['message' => $message], $code);
    }

    public function createJsonFromInstance(LtiInstance $entity, int $code): JsonResponse
    {
        return $this->responder->createJsonResponse(Model::fromEntity($entity), $code);
    }

    public function createJsonFromCollection(array $collection, int $code): JsonResponse
    {
        $res = [];

        foreach ($collection as $item) {
            $res[] = Model::fromEntity($item);
        }

        return $this->responder->createJsonResponse($res, $code);
    }
}