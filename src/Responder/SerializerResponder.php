<?php declare(strict_types=1);

namespace App\Responder;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;

class SerializerResponder
{
    public const DEFAULT_ERROR_MESSAGE = 'An error occurred.';

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
        $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : $statusCode;

        $content = [
            'message' => $statusCode < 500 ? $exception->getMessage() : self::DEFAULT_ERROR_MESSAGE
        ];

        if ($this->debug) {
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
