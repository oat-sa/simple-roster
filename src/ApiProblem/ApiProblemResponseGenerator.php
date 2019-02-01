<?php

namespace App\ApiProblem;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiProblemResponseGenerator implements ApiProblemResponseGeneratorInterface
{
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function supports(Request $request, \Throwable $exception): bool
    {
        // only apply if it is a JSON request
        if (
            false === mb_strpos($request->getRequestFormat(), 'json') &&
            false === mb_strpos((string) $request->getContentType(), 'json')
        ) {
            return false;
        }

        // only apply if debug mode is off or it is not 500 otherwise let Symfony handle it as usual
        // so in development we can see the proper error page with detailed information
        if ($this->debug && $this->getStatusCode($exception) >= 500) {
            return false;
        }

        return true;
    }

    public function generateResponse(Request $request, \Throwable $exception): JsonResponse
    {
        // finally we can start working with the ApiProblem object
        $apiProblem = $this->convertExceptionToProblem($exception, $this->getStatusCode($exception));

        // let's generate the proper JSON response
        return new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode(),
            ['Content-Type' => 'application/problem+json']
        );
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof BadCredentialsException) {
            return 403;
        }

        return 500;
    }

    /**
     * Converts exception into an ApiProblem object.
     */
    private function convertExceptionToProblem(\Throwable $exception, int $statusCode): ApiProblemInterface
    {
        if ($exception instanceof ApiProblemException) {
            $apiProblem = $exception->getApiProblem();
        } else {
            $apiProblem = new HttpApiProblem($statusCode);

            /*
             * If it's an HttpException message (e.g. for 404, 403),
             * we'll say as a rule that the exception message is safe
             * for the client. Otherwise, it could be some sensitive
             * low-level exception, which should *not* be exposed
             */
            if ($exception instanceof HttpExceptionInterface
                || $exception instanceof BadCredentialsException) {
                $apiProblem->setDetail($exception->getMessage());
            }
        }

        return $apiProblem;
    }
}
