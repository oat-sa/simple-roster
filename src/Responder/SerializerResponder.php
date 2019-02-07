<?php declare(strict_types=1);

namespace App\Responder;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class SerializerResponder
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

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
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : $headers;

        $responseBody = [
            'error' => [
                'code' => $statusCode,
                'message' => $exception->getMessage(),
            ]
        ];

        return JsonResponse::fromJsonString(
            $this->serializer->serialize($responseBody, 'json'),
            $statusCode,
            $headers
        );
    }
}
