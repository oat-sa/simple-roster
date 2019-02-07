<?php declare(strict_types=1);

namespace App\Action;

use App\Responder\SerializerResponder;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginAction
{
    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(SerializerResponder $serializerResponder)
    {
        $this->serializerResponder = $serializerResponder;
    }

    public function __invoke(): JsonResponse
    {
        return $this->serializerResponder->createJsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
