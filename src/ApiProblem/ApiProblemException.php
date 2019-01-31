<?php

namespace App\ApiProblem;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiProblemException extends HttpException
{
    /**
     * @var ApiProblemInterface
     */
    private $apiProblem;

    public function __construct(ApiProblemInterface $apiProblem, \Exception $previous = null, array $headers = array(), $code = 0)
    {
        $this->apiProblem = $apiProblem;

        parent::__construct($apiProblem->getStatusCode(), $apiProblem->getTitle(), $previous, $headers, $code);
    }

    public function getApiProblem(): ApiProblemInterface
    {
        return $this->apiProblem;
    }
}