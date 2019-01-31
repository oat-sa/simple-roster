<?php

namespace App\ApiProblem;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

interface ApiProblemResponseGeneratorInterface
{
    /**
     * Is API Problem Response required?
     */
    public function supports(Request $request, \Throwable $exception): bool ;

    public function generateResponse(Request $request, \Throwable $exception): JsonResponse ;
}