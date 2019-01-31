<?php declare(strict_types=1);

namespace App\Security;

use App\ApiProblem\ApiProblemResponseGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class ApiAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private $apiResponseGenerator;
    private $logger;

    public function __construct(ApiProblemResponseGeneratorInterface $apiResponseGenerator, LoggerInterface $logger)
    {
        $this->apiResponseGenerator = $apiResponseGenerator;
        $this->logger = $logger;
    }

    /**
     * AuthenticationException is not handled inside of the standard kernel.exception event,
     * so we have to generate the proper json response here.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $this->logger->error($exception->getMessage());

        return $this->apiResponseGenerator->generateResponse($request, $exception);
    }
}