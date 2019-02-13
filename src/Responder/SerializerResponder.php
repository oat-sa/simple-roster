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
    
    /** @var bool */
    private $debug;

    public function __construct(SerializerInterface $serializer, bool $debug = false)
    {
        $this->serializer = $serializer;
        $this->debug = $debug;
    }

    public function createJsonResponse($data, int $statusCode = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return JsonResponse::fromJsonString(
            $this->serializer->serialize($data, 'json'),
            $statusCode,
            $headers
        );
    }

    public function createErrorJsonResponse(Throwable $exception, int $statusCode = 500, array $headers = []): JsonResponse
    {
        $content = [
            'message' => $exception->getMessage()
        ];

        if ($this->debug) {
            $content['trace'] = $exception->getTraceAsString();
        }

        return JsonResponse::fromJsonString(
            $this->serializer->serialize(['error' => $content], 'json'),
            $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : $statusCode,
            $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : $headers
        );
    }
}
