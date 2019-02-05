<?php declare(strict_types=1);

namespace App\Responder;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

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
}
