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

namespace OAT\SimpleRoster\Responder;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class SerializerResponder
{
    public const DEFAULT_ERROR_MESSAGE = 'An error occurred.';

    /** @var SerializerInterface */
    private $serializer;

    /** @var KernelInterface */
    private $kernel;

    public function __construct(SerializerInterface $serializer, KernelInterface $kernel)
    {
        $this->serializer = $serializer;
        $this->kernel = $kernel;
    }

    /**
     * @param mixed $data
     */
    public function createJsonResponse($data, int $statusCode = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return JsonResponse::fromJsonString(
            $this->serializer->serialize($data, 'json'),
            $statusCode,
            $headers
        );
    }

    public function createErrorJsonResponse(
        Throwable $exception,
        int $statusCode = 500,
        array $headers = []
    ): JsonResponse {
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : $statusCode;

        $content = [
            'message' => $statusCode < 500 ? $exception->getMessage() : self::DEFAULT_ERROR_MESSAGE,
        ];

        if ($this->kernel->isDebug()) {
            $content['message'] = $exception->getMessage();
            $content['trace'] = $exception->getTraceAsString();
        }

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(['error' => $content], 'json'),
            $statusCode,
            $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : $headers
        );
    }
}
