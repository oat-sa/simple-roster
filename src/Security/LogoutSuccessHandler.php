<?php

namespace App\Security;

use App\Responder\SerializerResponder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /** @var SerializerResponder */
    private $serializerResponder;

    public function __construct(SerializerResponder $serializerResponder)
    {
        $this->serializerResponder = $serializerResponder;
    }

    public function onLogoutSuccess(Request $request)
    {
        return $this->serializerResponder->createJsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }
}
